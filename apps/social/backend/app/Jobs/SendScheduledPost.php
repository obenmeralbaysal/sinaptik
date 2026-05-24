<?php

namespace App\Jobs;

use App\Models\Post;
use App\Services\X\XClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendScheduledPost implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(public int $postId) {}

    public function handle(XClient $x): void
    {
        $post = Post::withoutGlobalScopes()->with('socialAccount')->find($this->postId);
        if (! $post || $post->status !== 'pending') {
            return;
        }

        try {
            $result = $x->postContent($post->socialAccount, $post->content);
            $post->update([
                'status' => 'posted',
                'posted_at' => now(),
                'provider_post_id' => $result['id'] ?? null,
                'error' => null,
            ]);
        } catch (\Throwable $e) {
            $post->update(['status' => 'failed', 'error' => $e->getMessage()]);
            throw $e;
        }
    }
}
