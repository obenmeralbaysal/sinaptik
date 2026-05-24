<?php

namespace App\Jobs;

use App\Models\Reply;
use App\Models\SocialAccount;
use App\Models\Target;
use App\Services\AI\AIManager;
use App\Services\X\XClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessTargetReplies implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(public int $socialAccountId) {}

    public function handle(XClient $x, AIManager $aiManager): void
    {
        /** @var SocialAccount|null $account */
        $account = SocialAccount::withoutGlobalScopes()->with('organization')->find($this->socialAccountId);
        if (! $account || ! $account->is_active || ! $account->organization) {
            return;
        }

        try {
            $ai = $aiManager->forOrganization($account->organization);
        } catch (\Throwable $e) {
            Log::warning("ProcessTargetReplies: AI not configured for org {$account->organization_id}: ".$e->getMessage());
            return;
        }

        // Frequency check
        $freq = $account->reply_frequency_minutes ?? 10;
        if ($account->last_reply_check_at && $account->last_reply_check_at->addMinutes($freq)->isFuture()) {
            return;
        }

        $account->forceFill(['last_reply_check_at' => now()])->save();

        // Daily limit
        $dailyLimit = $account->daily_reply_limit ?? 10;
        $todayCount = Reply::withoutGlobalScopes()
            ->where('social_account_id', $account->id)
            ->whereDate('created_at', now()->toDateString())
            ->count();

        if ($todayCount >= $dailyLimit) {
            return;
        }

        $targets = Target::withoutGlobalScopes()
            ->where('organization_id', $account->organization_id)
            ->where('is_active', true)
            ->where(function ($q) use ($account) {
                $q->whereNull('social_account_id')->orWhere('social_account_id', $account->id);
            })
            ->get();

        foreach ($targets as $target) {
            if ($todayCount >= $dailyLimit) break;

            $targetTodayCount = Reply::withoutGlobalScopes()
                ->where('target_id', $target->id)
                ->whereDate('created_at', now()->toDateString())
                ->count();

            if ($targetTodayCount >= ($target->daily_limit ?? 10)) continue;

            $lastForTarget = Reply::withoutGlobalScopes()
                ->where('target_id', $target->id)
                ->latest('created_at')->first();

            if ($lastForTarget && $lastForTarget->created_at->addMinutes($target->reply_frequency_minutes ?? 60)->isFuture()) {
                continue;
            }

            $remaining = min($dailyLimit - $todayCount, ($target->daily_limit ?? 10) - $targetTodayCount, 10);
            if ($remaining <= 0) continue;

            try {
                $tweets = $x->fetchUserTimeline($account, $target->target_username, $remaining);
            } catch (\Throwable $e) {
                Log::warning("ProcessTargetReplies: timeline fetch failed for {$target->target_username}: ".$e->getMessage());
                continue;
            }

            foreach ($tweets as $tweet) {
                if ($todayCount >= $dailyLimit) break;

                $existing = Reply::withoutGlobalScopes()
                    ->where('social_account_id', $account->id)
                    ->where('original_post_id', $tweet['id'])
                    ->exists();
                if ($existing) continue;

                $model = $target->ai_model ?? $account->ai_model;

                try {
                    $replyText = $ai->generateReply(
                        $tweet['text'],
                        $target->ai_tone ?? 'supportive',
                        $target->custom_prompt,
                        $model
                    );
                } catch (\Throwable $e) {
                    Log::warning("ProcessTargetReplies: AI failed: ".$e->getMessage());
                    continue;
                }

                if (! $replyText) continue;

                try {
                    $result = $x->replyToPost($account, $tweet['id'], $replyText);
                    Reply::create([
                        'organization_id' => $account->organization_id,
                        'social_account_id' => $account->id,
                        'target_id' => $target->id,
                        'original_post_id' => $tweet['id'],
                        'reply_content' => $replyText,
                        'status' => 'posted',
                        'provider_reply_id' => $result['id'] ?? null,
                        'posted_at' => now(),
                    ]);
                    $todayCount++;
                    $targetTodayCount++;
                } catch (\Throwable $e) {
                    Log::warning("ProcessTargetReplies: reply failed: ".$e->getMessage());
                }
            }
        }
    }
}
