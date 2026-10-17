<?php

namespace App\Jobs;

use App\Jobs\Middleware\SetWorkspaceContext;
use App\Models\MediaAsset;
use App\Models\Post;
use App\Models\Workspace;
use App\Notifications\WorkspaceAlert;
use App\Services\MediaLibrary;
use App\Services\Meta\MetaDriver;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class PublishPost implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public function __construct(public int $postId, public int $workspaceId)
    {
        $this->onQueue('publish');
    }

    public function backoff(): array
    {
        return [30, 120, 300, 900];
    }

    public function middleware(): array
    {
        return [new SetWorkspaceContext($this->workspaceId)];
    }

    public function handle(MetaDriver $meta, MediaLibrary $media): void
    {
        $post = Post::query()->find($this->postId);
        if (! $post || ! in_array($post->status, ['scheduled', 'publishing'], true)) {
            return;
        }

        $workspace = Workspace::query()->find($this->workspaceId);
        if ($workspace?->isReadOnly()) {
            $post->update(['status' => 'paused']);

            return;
        }

        $account = $post->account;
        if (! $account || $account->status !== 'connected') {
            $post->update(['status' => 'failed', 'failure_reason' => 'Hesap bağlı değil.']);

            return;
        }

        $urls = MediaAsset::query()->whereIn('id', $post->media_ids ?? [])->get()
            ->map(fn (MediaAsset $asset) => $media->signedUrl($asset))->all();

        $result = $meta->publish($account, $post, $urls);
        $post->update([
            'status' => 'published',
            'external_id' => $result['id'] ?? null,
            'published_at' => now(),
            'failure_reason' => null,
        ]);
    }

    public function onWorkspaceFailure(\Throwable $exception): void
    {
        $post = Post::query()->find($this->postId);
        if (! $post) {
            return;
        }
        $post->update([
            'status' => 'failed',
            'failure_reason' => mb_substr($exception->getMessage(), 0, 500),
            'attempt_count' => $post->attempt_count + 1,
        ]);
        $post->author?->notify(new WorkspaceAlert('Gönderi yayınlanamadı', $post->failure_reason ?? 'Bilinmeyen hata'));
    }
}
