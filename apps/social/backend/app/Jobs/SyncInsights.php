<?php

namespace App\Jobs;

use App\Jobs\Middleware\SetWorkspaceContext;
use App\Models\AccountInsight;
use App\Models\Post;
use App\Models\PostInsight;
use App\Models\SocialAccount;
use App\Services\ConsentService;
use App\Services\Meta\MetaDriver;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncInsights implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $socialAccountId, public int $workspaceId)
    {
        $this->onQueue('insights-sync');
    }

    public function middleware(): array
    {
        return [new SetWorkspaceContext($this->workspaceId)];
    }

    public function handle(MetaDriver $meta, ConsentService $consents): void
    {
        if (! $consents->allowsSync($this->workspaceId)) {
            return;
        }

        $account = SocialAccount::query()->find($this->socialAccountId);
        if (! $account || ! in_array($account->platform, ['facebook_page', 'instagram'], true)) {
            return;
        }

        AccountInsight::query()->updateOrCreate(
            ['social_account_id' => $account->id, 'metric_date' => now()->toDateString()],
            ['workspace_id' => $account->workspace_id, 'metrics' => $meta->accountInsights($account)],
        );

        Post::query()->where('social_account_id', $account->id)->where('status', 'published')->whereNotNull('external_id')->each(function (Post $post) use ($meta, $account) {
            PostInsight::query()->updateOrCreate(
                ['post_id' => $post->id],
                [
                    'workspace_id' => $post->workspace_id,
                    'metrics' => $meta->postInsights($account, (string) $post->external_id),
                    'synced_at' => now(),
                ],
            );
        });
    }
}
