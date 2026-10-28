<?php

namespace App\Services\Comments;

use App\Models\Post;
use App\Models\SocialAccount;
use App\Models\SocialComment;
use App\Services\ConsentService;
use App\Services\Meta\MetaDriver;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use RuntimeException;

class CommentService
{
    public function __construct(
        private MetaDriver $meta,
        private ConsentService $consents,
    ) {}

    /** @return Collection<int, SocialComment> */
    public function syncPost(Post $post): Collection
    {
        $post->loadMissing('account');
        $account = $post->account;
        if ($post->status !== 'published' || ! $post->external_id || ! $account) {
            throw new RuntimeException('Yorumlar yalnızca yayınlanmış ve Meta kimliği olan gönderilerde listelenir.');
        }
        if (! in_array($account->platform, ['facebook_page', 'instagram'], true)) {
            throw new RuntimeException('Bu hesap türünde yorum eşitlenmez.');
        }
        if (! $this->consents->allowsSync($post->workspace_id)) {
            throw new RuntimeException('Meta açık rızası olmadan yorum çekilmez.');
        }

        $rows = $this->meta->listComments($account, (string) $post->external_id);
        foreach ($rows as $row) {
            if (($row['external_id'] ?? '') === '') {
                continue;
            }
            $this->upsert($account, $post, $row);
        }

        return $this->forPost($post);
    }

    public function syncWorkspace(int $limit = 30): int
    {
        $posts = Post::query()
            ->where('status', 'published')
            ->whereNotNull('external_id')
            ->whereNotNull('social_account_id')
            ->orderByDesc('published_at')
            ->limit($limit)
            ->get();

        $synced = 0;
        foreach ($posts as $post) {
            try {
                $this->syncPost($post);
                $synced++;
            } catch (RuntimeException) {
                continue;
            }
        }

        return $synced;
    }

    /** @return Collection<int, SocialComment> */
    public function forPost(Post $post): Collection
    {
        return SocialComment::query()
            ->where('post_id', $post->id)
            ->whereNull('purged_at')
            ->orderBy('posted_at')
            ->orderBy('id')
            ->get();
    }

    /** @return Collection<int, SocialComment> */
    public function inbox(int $limit = 100): Collection
    {
        return SocialComment::query()
            ->whereNull('purged_at')
            ->orderByDesc('posted_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    public function reply(SocialComment $comment, string $message): SocialComment
    {
        $comment->loadMissing('account', 'post');
        $account = $comment->account;
        if (! $account) {
            throw new RuntimeException('Yorumun bağlı hesabı yok.');
        }
        if (! $this->consents->allowsSync($comment->workspace_id)) {
            throw new RuntimeException('Meta açık rızası olmadan yorum yanıtlanmaz.');
        }

        $result = $this->meta->replyToComment($account, $comment->external_id, $message);

        return $this->upsert($account, $comment->post, [
            'external_id' => $result['external_id'],
            'parent_external_id' => $comment->external_id,
            'author_external_id' => $account->external_id,
            'author_username' => $account->username ?: $account->name,
            'body' => $message,
            'posted_at' => now()->toIso8601String(),
            'hidden' => false,
            'raw' => ['id' => $result['external_id'], 'source' => 'reply'],
        ]);
    }

    public function hide(SocialComment $comment, bool $hide = true): SocialComment
    {
        $comment->loadMissing('account');
        $account = $comment->account;
        if (! $account) {
            throw new RuntimeException('Yorumun bağlı hesabı yok.');
        }
        $this->meta->hideComment($account, $comment->external_id, $hide);
        $comment->update(['hidden_at' => $hide ? now() : null]);

        return $comment->fresh();
    }

    public function delete(SocialComment $comment): void
    {
        $comment->loadMissing('account');
        $account = $comment->account;
        if (! $account) {
            throw new RuntimeException('Yorumun bağlı hesabı yok.');
        }
        $this->meta->deleteComment($account, $comment->external_id);
        $comment->delete();
    }

    public function purgeExpired(): int
    {
        $cutoff = now()->subDays((int) config('sinsocial.comment_retention_days', 90));
        $count = 0;

        SocialComment::query()
            ->whereNull('purged_at')
            ->where(function ($query) use ($cutoff) {
                $query->where('posted_at', '<', $cutoff)
                    ->orWhere(function ($inner) use ($cutoff) {
                        $inner->whereNull('posted_at')->where('created_at', '<', $cutoff);
                    });
            })
            ->orderBy('id')
            ->each(function (SocialComment $comment) use (&$count) {
                $comment->update([
                    'author_external_id' => null,
                    'author_username' => null,
                    'body' => null,
                    'raw' => null,
                    'purged_at' => now(),
                ]);
                $count++;
            });

        return $count;
    }

    /** @param array<string, mixed> $row */
    private function upsert(SocialAccount $account, ?Post $post, array $row): SocialComment
    {
        $postedAt = ! empty($row['posted_at']) ? Carbon::parse((string) $row['posted_at']) : null;
        $retentionCutoff = now()->subDays((int) config('sinsocial.comment_retention_days', 90));
        $expired = $postedAt && $postedAt->lt($retentionCutoff);
        $existing = SocialComment::withTrashed()
            ->where('social_account_id', $account->id)
            ->where('external_id', (string) $row['external_id'])
            ->first();

        if ($existing?->purged_at || $expired) {
            if ($existing) {
                return $existing;
            }

            return SocialComment::query()->create([
                'workspace_id' => $account->workspace_id,
                'social_account_id' => $account->id,
                'post_id' => $post?->id,
                'platform' => $account->platform === 'instagram' ? 'instagram' : 'facebook',
                'external_id' => (string) $row['external_id'],
                'parent_external_id' => $row['parent_external_id'] ?? null,
                'posted_at' => $postedAt,
                'hidden_at' => ! empty($row['hidden']) ? now() : null,
                'purged_at' => now(),
                'last_synced_at' => now(),
            ]);
        }

        if ($existing?->trashed()) {
            $existing->restore();
        }

        return SocialComment::query()->updateOrCreate(
            [
                'social_account_id' => $account->id,
                'external_id' => (string) $row['external_id'],
            ],
            [
                'workspace_id' => $account->workspace_id,
                'post_id' => $post?->id ?? $existing?->post_id,
                'platform' => $account->platform === 'instagram' ? 'instagram' : 'facebook',
                'parent_external_id' => $row['parent_external_id'] ?? null,
                'author_external_id' => $row['author_external_id'] ?? null,
                'author_username' => $row['author_username'] ?? null,
                'body' => $row['body'] ?? null,
                'posted_at' => $postedAt,
                'hidden_at' => ! empty($row['hidden']) ? ($existing?->hidden_at ?? now()) : null,
                'raw' => $row['raw'] ?? null,
                'last_synced_at' => now(),
                'purged_at' => null,
            ],
        );
    }
}
