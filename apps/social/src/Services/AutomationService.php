<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Post;
use App\Models\Target;
use App\Models\Reply;
use App\Providers\XProvider;

class AutomationService
{
    public function processScheduledPosts()
    {
        $pendingPosts = Post::getPending();
        $xProvider = new XProvider();

        \App\Core\Logger::log("Starting processScheduledPosts. Found " . count($pendingPosts) . " pending posts.");

        foreach ($pendingPosts as $post) {
            $this->sendSinglePost($post, $xProvider);
        }
    }

    public function sendSinglePost($post, $xProvider = null)
    {
        if (!$xProvider) {
            $xProvider = new XProvider();
        }

        $xProvider->authenticate([
            'id' => $post['account_pk'], // Important for updating tokens in DB
            'username' => $post['username'], // Needed for additional_owners in uploadMedia
            'api_key' => $post['api_key'],
            'api_secret' => $post['api_secret'],
            'access_token' => $post['access_token'],
            'access_token_secret' => $post['access_token_secret'],
            'refresh_token' => $post['refresh_token'],
            'expires_at' => $post['expires_at']
        ]);

        \App\Core\Logger::log("Attempting to post scheduled tweet ID: {$post['id']} for account: {$post['username']}");

        $mediaIds = [];
        if (!empty($post['media_url'])) {
            // If it's a relative path (e.g. /uploads/...), prepend document root
            $filePath = $post['media_url'];
            \App\Core\Logger::log("DEBUG: Processing media_url: " . $filePath);

            if (strpos($filePath, '/') === 0 && file_exists(__DIR__ . '/../../public' . $filePath)) {
                $filePath = __DIR__ . '/../../public' . $filePath;
                \App\Core\Logger::log("DEBUG: Resolved local path: " . $filePath);
            } elseif (strpos($filePath, 'http') === 0) {
                // Remote URL - might need to download first if X requires local file
                // XProvider::uploadMedia uses CURLFile so it needs a local path.
                // Let's temp download it.
                $tempFile = sys_get_temp_dir() . '/' . basename($filePath);
                file_put_contents($tempFile, file_get_contents($filePath));
                $filePath = $tempFile;
                \App\Core\Logger::log("DEBUG: Downloaded remote file to: " . $filePath);
            } else {
                \App\Core\Logger::log("DEBUG: Could not resolve file path for: " . $filePath);
            }

            $mediaId = $xProvider->uploadMedia($filePath);
            if ($mediaId) {
                $mediaIds[] = $mediaId;
                \App\Core\Logger::log("Attached media ID: $mediaId");
            } else {
                \App\Core\Logger::log("Failed to upload media: {$post['media_url']}");
            }
        }

        if ($xProvider->post($post['content'], $mediaIds)) {
            Post::updateStatus($post['id'], 'posted');
            \App\Core\Logger::log("Scheduled post ID {$post['id']} sent successfully.");
            return true;
        } else {
            Post::updateStatus($post['id'], 'failed');
            \App\Core\Logger::log("Scheduled post ID {$post['id']} failed to send.");
            return false;
        }
    }

    public function retryFailedPosts()
    {
        $failedPosts = Post::getFailed();
        $xProvider = new XProvider();

        \App\Core\Logger::log("Starting retryFailedPosts. Found " . count($failedPosts) . " failed posts.");

        foreach ($failedPosts as $post) {
            $xProvider->authenticate([
                'id' => $post['account_pk'],
                'username' => $post['username'],
                'api_key' => $post['api_key'],
                'api_secret' => $post['api_secret'],
                'access_token' => $post['access_token'],
                'access_token_secret' => $post['access_token_secret'],
                'refresh_token' => $post['refresh_token'],
                'expires_at' => $post['expires_at']
            ]);

            \App\Core\Logger::log("Retrying post ID: {$post['id']} for account: {$post['username']}");

            if ($xProvider->post($post['content'])) {
                Post::updateStatus($post['id'], 'posted');
                \App\Core\Logger::log("Retry success: Post ID {$post['id']} sent.");
            } else {
                // Keep as failed or update timestamp? optimize to not loop forever?
                // For this one-off request, leave as failed.
                \App\Core\Logger::log("Retry failed: Post ID {$post['id']} still failed.");
            }
        }
    }

    public function generateDailyPosts()
    {
        $accounts = Account::allActive();
        $aiService = new AIService();

        foreach ($accounts as $account) {
            // Get user plan limits
            $plan = \App\Models\User::getPlan($account['user_id']);
            // Default limit changed to 0 as per user request to disable auto-posting by default
            $postLimit = $plan ? ($plan['daily_post_limit'] ?? 0) : 0;

            if ($postLimit <= 0) {
                continue;
            }

            // Generate posts based on plan limit
            for ($i = 0; $i < $postLimit; $i++) {
                $content = $aiService->generatePost("social media engagement");

                // Check for error messages or empty content
                if (
                    empty($content) ||
                    stripos($content, 'Unable to generate response') !== false ||
                    stripos($content, 'Error') === 0
                ) {
                    \App\Core\Logger::log("Skipping daily post generation for account {$account['id']} - AI Generation Failed: " . $content);
                    continue;
                }

                // Distribute posts throughout the day (e.g., every 4 hours or dynamically based on limit)
                $interval = $postLimit > 0 ? (24 / $postLimit) : 8;
                $extraHours = ($i * $interval);
                $scheduledAt = date('Y-m-d H:i:s', strtotime("+$extraHours hours"));

                Post::create([
                    'account_id' => $account['id'],
                    'content' => $content,
                    'status' => 'pending',
                    'scheduled_at' => $scheduledAt
                ]);
            }
        }
    }

    public function processReplies()
    {
        $accounts = Account::allActive();
        $targets = Target::allActive();
        $aiService = new AIService();
        $xProvider = new XProvider();

        \App\Core\Logger::log("Starting processReplies for " . count($accounts) . " accounts.");

        foreach ($accounts as $account) {
            // 1. Check Frequency
            $lastCheck = $account['last_reply_check_at'] ? strtotime($account['last_reply_check_at']) : 0;
            $frequencyMinutes = $account['reply_frequency_minutes'] ?? 10;
            $nextCheck = $lastCheck + ($frequencyMinutes * 60);

            if (time() < $nextCheck) {
                // \App\Core\Logger::log("Skipping Account {$account['id']} due to frequency limit (Next check: " . date('Y-m-d H:i:s', $nextCheck) . ")");
                continue; // Too soon
            }

            // Update check time immediately to prevent race conditions if process takes long
            Account::updateLastReplyCheck($account['id']);

            // 2. Check Daily Limit
            $dailyLimit = $account['daily_reply_limit'] ?? 10;
            $currentCount = Reply::countToday($account['id']);

            if ($currentCount >= $dailyLimit) {
                \App\Core\Logger::log("Skipping Account {$account['id']} - Daily limit reached ($currentCount/$dailyLimit)");
                continue;
            }

            $xProvider->authenticate($account);

            foreach ($targets as $target) {
                if ($currentCount >= $dailyLimit)
                    break;

                if (!empty($target['account_id']) && $target['account_id'] != $account['id']) {
                    continue;
                }

                // CHECK TARGET LIMITS
                $targetDailyLimit = $target['daily_limit'] ?? 10;
                $targetCurrentCount = Reply::countTodayForTarget($target['id']);

                if ($targetCurrentCount >= $targetDailyLimit) {
                    \App\Core\Logger::log("Skipping Target {$target['target_username']} - Target daily limit reached ($targetCurrentCount/$targetDailyLimit)");
                    continue;
                }

                // CHECK TARGET FREQUENCY
                $targetLastReply = Reply::getLastReplyTimeForTarget($target['id']);
                $targetFrequency = $target['reply_frequency_minutes'] ?? 60; // Default 60 mins per target

                if ($targetLastReply && (time() - strtotime($targetLastReply)) < ($targetFrequency * 60)) {
                    // \App\Core\Logger::log("Skipping Target {$target['target_username']} - Frequency limit (Last reply: $targetLastReply)");
                    continue;
                }

                // Optimization: adjust fetch limit based on target's remaining allowance
                $targetRemaining = $targetDailyLimit - $targetCurrentCount;
                $accountRemaining = $dailyLimit - $currentCount;
                $fetchLimit = min($targetRemaining, $accountRemaining, 10);

                if ($fetchLimit <= 0)
                    continue;

                \App\Core\Logger::log("Checking target {$target['target_username']} for account {$account['id']} (Limit: $fetchLimit)");

                try {
                    $recentTweets = $xProvider->searchRecentPosts($target['target_username'], $fetchLimit);
                } catch (\Exception $e) {
                    \App\Core\Logger::log("Error fetching tweets for {$target['target_username']}: " . $e->getMessage());
                    continue;
                }

                if (!$recentTweets) {
                    \App\Core\Logger::log("No recent tweets found for {$target['target_username']} (or API error)");
                    continue;
                }

                foreach ($recentTweets as $tweet) {
                    if ($currentCount >= $dailyLimit)
                        break;

                    if (Reply::exists($tweet['id'])) {
                        // \App\Core\Logger::log("Skipping tweet {$tweet['id']} - Already replied");
                        continue;
                    }

                    \App\Core\Logger::log("Generating reply for tweet {$tweet['id']} by {$target['target_username']}");

                    // Prioritize Target model, then Account model, then default
                    $model = $target['ai_model'] ?? $account['ai_model'] ?? 'gemini-1.5-flash';

                    // Use 'custom' tone effectively by passing the custom prompt. 
                    // If target has specific instructions, we pass them.
                    $replyContent = $aiService->generateReply(
                        $tweet['text'],
                        'custom', // Force custom tone
                        $target['custom_prompt'] ?? null,
                        $model
                    );

                    // Check for error messages or empty content
                    if (
                        empty($replyContent) ||
                        stripos($replyContent, 'Unable to generate response') !== false ||
                        stripos($replyContent, 'Error') === 0
                    ) {
                        \App\Core\Logger::log("Skipping reply for tweet {$tweet['id']} - AI Generation Failed: " . $replyContent);
                        continue;
                    }

                    if ($xProvider->reply($tweet['id'], $replyContent)) {
                        Reply::create([
                            'account_id' => $account['id'],
                            'target_id' => $target['id'],
                            'original_post_id' => $tweet['id'],
                            'reply_content' => $replyContent,
                            'status' => 'posted'
                        ]);
                        $currentCount++;
                        \App\Core\Logger::log("Reply posted successfully to {$tweet['id']}");
                    } else {
                        \App\Core\Logger::log("Failed to post reply to {$tweet['id']}");
                    }
                }
            }
        }
    }
    public function fetchXmlFeeds()
    {
        $sources = \App\Models\XmlSource::getDueSources();

        foreach ($sources as $source) {
            try {
                $xmlContent = file_get_contents($source['url']);
                if (!$xmlContent)
                    continue;

                $xml = new \SimpleXMLElement($xmlContent);

                // Selector logic: Assuming simple node path like 'channel/item/title' or 'currency/usd'
                // For robustness, we can use XPath if selector starts with '/'
                if (strpos($source['selector'], '/') === 0) {
                    $result = $xml->xpath($source['selector']);
                    $value = $result ? (string) $result[0] : null;
                } else {
                    // Try to traverse properties
                    $nodes = explode('/', $source['selector']);
                    $temp = $xml;
                    foreach ($nodes as $node) {
                        if (isset($temp->$node)) {
                            $temp = $temp->$node;
                        } else {
                            $temp = null;
                            break;
                        }
                    }
                    $value = $temp ? (string) $temp : null;
                }

                if ($value) {
                    $content = str_replace('{value}', $value, $source['template']);

                    // Check if a post with this content already exists for today to avoid spamming the same value?
                    // For now, let's just create it.

                    // We need an account to post to. 
                    // The XML source currently matches a user, but we need to know WHICH account.
                    // Let's pick the first active X account for the user for now, or we should have added account_id to xml_sources.
                    // CRITICAL: xml_sources table does not have account_id. 
                    // Let's assume we post to ALL active X accounts of the user? Or pick one?
                    // Let's pick the first one.
                    $accounts = Account::allActiveForUser($source['user_id']);
                    if (empty($accounts))
                        continue;

                    $targetAccount = $accounts[0];

                    // Create Post
                    Post::create([
                        'account_id' => $targetAccount['id'],
                        'content' => $content,
                        'status' => 'pending',
                        'scheduled_at' => date('Y-m-d H:i:s') // Post immediately (processed by posts:process next minute)
                    ]);

                    echo "Generated post for XML Source: {$source['name']}\n";
                }
            } catch (\Exception $e) {
                // Log error
                echo "Error processing XML Source {$source['id']}: " . $e->getMessage() . "\n";
            }
        }
    }
}
