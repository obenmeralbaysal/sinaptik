<?php

namespace App\Services;

use App\Models\Consent;
use App\Models\User;
use App\Models\Workspace;

class ConsentService
{
    public function current(string $provider = 'meta'): array
    {
        return [
            'provider' => $provider,
            'text' => (string) config("consent.{$provider}.text"),
            'text_version' => $this->textVersion($provider),
            'notice_url' => (string) config("consent.{$provider}.notice_url"),
        ];
    }

    public function textVersion(string $provider = 'meta'): string
    {
        return (string) config("consent.{$provider}.text_version");
    }

    public function activeForUser(int $workspaceId, int $userId, string $provider = 'meta'): ?Consent
    {
        return Consent::query()
            ->withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->where('user_id', $userId)
            ->where('provider', $provider)
            ->where('text_version', $this->textVersion($provider))
            ->whereNull('withdrawn_at')
            ->latest('granted_at')
            ->first();
    }

    public function allowsSync(int $workspaceId, string $provider = 'meta'): bool
    {
        return Consent::query()
            ->withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->where('provider', $provider)
            ->where('text_version', $this->textVersion($provider))
            ->whereNull('withdrawn_at')
            ->exists();
    }

    public function grant(Workspace $workspace, User $user, string $provider, string $textVersion): Consent
    {
        $current = $this->textVersion($provider);
        if ($textVersion !== $current) {
            abort(422, 'Açık rıza metni güncellendi. Yeniden onaylayın.');
        }

        $existing = $this->activeForUser($workspace->id, $user->id, $provider);
        if ($existing) {
            return $existing;
        }

        return Consent::query()->create([
            'user_id' => $user->id,
            'workspace_id' => $workspace->id,
            'provider' => $provider,
            'text_version' => $current,
            'granted_at' => now(),
        ]);
    }

    public function withdraw(Workspace $workspace, string $provider = 'meta'): void
    {
        Consent::query()
            ->where('workspace_id', $workspace->id)
            ->where('provider', $provider)
            ->whereNull('withdrawn_at')
            ->update(['withdrawn_at' => now()]);
    }
}
