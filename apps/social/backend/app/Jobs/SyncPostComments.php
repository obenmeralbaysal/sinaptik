<?php

namespace App\Jobs;

use App\Jobs\Middleware\SetWorkspaceContext;
use App\Models\Post;
use App\Services\Comments\CommentService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use RuntimeException;

class SyncPostComments implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $postId, public int $workspaceId)
    {
        $this->onQueue('insights-sync');
    }

    public function middleware(): array
    {
        return [new SetWorkspaceContext($this->workspaceId)];
    }

    public function handle(CommentService $comments): void
    {
        $post = Post::query()->find($this->postId);
        if (! $post) {
            return;
        }

        try {
            $comments->syncPost($post);
        } catch (RuntimeException) {
            return;
        }
    }
}
