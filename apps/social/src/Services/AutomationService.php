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
            // Generate 3 posts for today
            for ($i = 0; $i < 3; $i++) {
                $content = $aiService->generatePost("social media engagement");
                $scheduledAt = date('Y-m-d H:i:s', strtotime("+" . ($i * 4) . " hours"));
                
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
            $dailyLimit = 10;
            $currentCount = Reply::countToday($account['id']);

            if ($currentCount >= $dailyLimit) continue;

            $xProvider->authenticate($account);

            foreach ($targets as $target) {
                if ($currentCount >= $dailyLimit) break;

                $recentTweets = $xProvider->searchRecentPosts($target['target_username'], 3);
                
                foreach ($recentTweets as $tweet) {
                    if ($currentCount >= $dailyLimit) break;
                    if (Reply::exists($tweet['id'])) continue;

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
