<?php

namespace App\Services\Publishing;

use App\Jobs\PublishPost;
use App\Models\Post;
use App\Models\PostApproval;
use App\Models\User;
use App\Models\Workspace;
use App\Services\LimitGuard;
use App\Services\UsageMeter;

class PostService
{
    public function __construct(
        private LimitGuard $limits,
        private UsageMeter $usage,
    ) {}

    public function create(Workspace $workspace, User $user, array $data): Post
    {
        $status = $data['status'] ?? 'draft';
        if (in_array($status, ['scheduled', 'publishing', 'approved'], true)) {
            $this->consumeQuota($workspace);
            $status = $this->resolveStatus($workspace, $status, $data['scheduled_at'] ?? null);
        }

        $post = Post::query()->create([
            'workspace_id' => $workspace->id,
            'brand_id' => $data['brand_id'] ?? null,
            'social_account_id' => $data['social_account_id'] ?? null,
            'author_id' => $user->id,
            'status' => $status,
            'post_type' => $data['post_type'] ?? 'feed',
            'body' => $data['body'] ?? null,
            'media_ids' => $data['media_ids'] ?? [],
            'scheduled_at' => $data['scheduled_at'] ?? null,
            'platform_options' => $data['platform_options'] ?? null,
            'quota_counted' => in_array($status, ['scheduled', 'publishing', 'pending_approval', 'approved'], true),
        ]);

        if ($post->status === 'pending_approval') {
            PostApproval::query()->create([
                'workspace_id' => $workspace->id,
                'post_id' => $post->id,
                'requested_by' => $user->id,
                'status' => 'pending',
            ]);
        }

        if (in_array($post->status, ['scheduled', 'publishing'], true)) {
            $this->dispatch($post);
        }

        return $post;
    }

    public function approve(Post $post, User $user, ?string $comment = null): Post
    {
        $approval = $post->approvals()->where('status', 'pending')->latest('id')->first();
        $approval?->update(['status' => 'approved', 'decided_by' => $user->id, 'comment' => $comment]);
        $status = $post->scheduled_at && $post->scheduled_at->isFuture() ? 'scheduled' : 'publishing';
        if (! $post->quota_counted) {
            $this->consumeQuota($post->workspace);
            $post->quota_counted = true;
        }
        $post->status = $status;
        $post->save();
        $this->dispatch($post);

        return $post;
    }

    public function reject(Post $post, User $user, ?string $comment = null): Post
    {
        $post->approvals()->where('status', 'pending')->update([
            'status' => 'rejected',
            'decided_by' => $user->id,
            'comment' => $comment,
        ]);
        $post->update(['status' => 'rejected']);

        return $post;
    }

    public function dispatch(Post $post): void
    {
        $pending = PublishPost::dispatch($post->id, $post->workspace_id);
        if ($post->status === 'scheduled' && $post->scheduled_at?->isFuture()) {
            $pending->delay($post->scheduled_at);
        }
    }

    private function resolveStatus(Workspace $workspace, string $requested, mixed $scheduledAt): string
    {
        if ($workspace->approval_required && $requested !== 'draft') {
            return 'pending_approval';
        }
        if ($scheduledAt && now()->parse($scheduledAt)->isFuture()) {
            return 'scheduled';
        }

        return 'publishing';
    }

    private function consumeQuota(Workspace $workspace): void
    {
        $this->limits->assert($workspace, 'posts_per_month');
        $this->usage->increment($workspace, 'posts');
    }
}
