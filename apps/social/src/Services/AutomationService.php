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
            // Get user plan limits
            $plan = \App\Models\User::getPlan($account['user_id']);
            $dailyLimit = $plan ? ($plan['daily_reply_limit'] ?? 10) : 10;

            $currentCount = Reply::countToday($account['id']);

            if ($currentCount >= $dailyLimit)
                continue;

            $xProvider->authenticate($account);

            foreach ($targets as $target) {
                if ($currentCount >= $dailyLimit)
                    break;

                // Determine how many tweets to fetch to potentially fill the remaining quota
                $remainingQuota = $dailyLimit - $currentCount;
                $fetchLimit = min($remainingQuota, 10); // Don't fetch too many at once

                $recentTweets = $xProvider->searchRecentPosts($target['target_username'], $fetchLimit);

                foreach ($recentTweets as $tweet) {
                    if ($currentCount >= $dailyLimit)
                        break;
                    if (Reply::exists($tweet['id']))
                        continue;

                    $replyContent = $aiService->generateReply($tweet['text'], $target['ai_tone']);

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
}
