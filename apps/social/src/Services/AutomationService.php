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

        foreach ($pendingPosts as $post) {
            $xProvider->authenticate([
                'api_key' => $post['api_key'],
                'api_secret' => $post['api_secret'],
                'access_token' => $post['access_token'],
                'access_token_secret' => $post['access_token_secret']
            ]);

            if ($xProvider->post($post['content'])) {
                Post::updateStatus($post['id'], 'posted');
            } else {
                Post::updateStatus($post['id'], 'failed');
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
            $postLimit = $plan ? ($plan['daily_post_limit'] ?? 3) : 3;

            // Generate posts based on plan limit
            for ($i = 0; $i < $postLimit; $i++) {
                $content = $aiService->generatePost("social media engagement");
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

        foreach ($accounts as $account) {
            // 1. Check Frequency
            $lastCheck = $account['last_reply_check_at'] ? strtotime($account['last_reply_check_at']) : 0;
            $frequencyMinutes = $account['reply_frequency_minutes'] ?? 10;
            $nextCheck = $lastCheck + ($frequencyMinutes * 60);

            if (time() < $nextCheck) {
                continue; // Too soon
            }

            // Update check time immediately to prevent race conditions if process takes long
            Account::updateLastReplyCheck($account['id']);

            // 2. Check Daily Limit
            $dailyLimit = $account['daily_reply_limit'] ?? 10;
            $currentCount = Reply::countToday($account['id']);

            if ($currentCount >= $dailyLimit) {
                continue;
            }

            $xProvider->authenticate($account);

            foreach ($targets as $target) {
                if ($currentCount >= $dailyLimit)
                    break;

                // Determine how many tweets to fetch to potentially fill the remaining quota
                $remainingQuota = $dailyLimit - $currentCount;
                $fetchLimit = min($remainingQuota, 10); // Don't fetch too many at once

                // Optimization: Only search if target is relevant to this account? 
                // Checks if target is global or has specific account binding?
                // Target::allActive() returns all. If target has account_id, maybe we should respect it?
                // Current logic implies all accounts reply to all targets, unless target has account_id?
                // Let's check Target model in next step if needed. For now, keep existing logic but respect account_id if present.
                if (!empty($target['account_id']) && $target['account_id'] != $account['id']) {
                    continue;
                }

                try {
                    $recentTweets = $xProvider->searchRecentPosts($target['target_username'], $fetchLimit);
                } catch (\Exception $e) {
                    continue;
                }

                if (!$recentTweets)
                    continue;

                foreach ($recentTweets as $tweet) {
                    if ($currentCount >= $dailyLimit)
                        break;
                    if (Reply::exists($tweet['id']))
                        continue;

                    // Pass the account's preferred model to generateReply
                    $model = $account['ai_model'] ?? 'gemini-1.5-flash';
                    $replyContent = $aiService->generateReply($tweet['text'], $target['ai_tone'], $target['custom_prompt'] ?? null, $model);

                    if ($xProvider->reply($tweet['id'], $replyContent)) {
                        Reply::create([
                            'account_id' => $account['id'],
                            'target_id' => $target['id'],
                            'original_post_id' => $tweet['id'],
                            'reply_content' => $replyContent,
                            'status' => 'posted'
                        ]);
                        $currentCount++;
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
