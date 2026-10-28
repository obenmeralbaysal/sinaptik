<?php

namespace App\Services\Meta;

use App\Models\AdAccount;
use App\Models\Consent;
use App\Models\SocialAccount;
use App\Models\User;
use App\Models\Workspace;
use App\Services\AuditLogger;
use App\Services\ConsentService;
use App\Services\LimitGuard;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class MetaConnectionService
{
    public function __construct(
        private MetaDriver $driver,
        private LimitGuard $limits,
        private ConsentService $consents,
        private AuditLogger $audit,
    ) {}

    public function start(Workspace $workspace, User $user, bool $accepted, string $textVersion): string
    {
        if (! $accepted) {
            abort(422, 'Açık rıza gerekli.');
        }

        $consent = $this->consents->grant($workspace, $user, 'meta', $textVersion);
        $state = Str::random(40);
        Cache::put($this->cacheKey($state), [
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'consent_id' => $consent->id,
        ], now()->addMinutes(15));

        return $this->driver->loginUrl($state, $this->redirectUri());
    }

    public function handleCallback(string $code, string $state): array
    {
        $cached = Cache::pull($this->cacheKey($state));
        if (! is_array($cached)) {
            abort(422, 'Bağlantı oturumu geçersiz.');
        }

        $this->assertCachedConsent($cached);

        $short = $this->driver->exchangeCode($code, $this->redirectUri());
        $long = $this->driver->exchangeLongLived($short['access_token']);
        $token = $long['access_token'];
        $debug = $this->driver->debugToken($token);

        $account = SocialAccount::query()->updateOrCreate(
            [
                'workspace_id' => $cached['workspace_id'],
                'platform' => 'meta_user',
                'external_id' => (string) ($debug['user_id'] ?? 'me'),
            ],
            [
                'name' => 'Meta kullanıcısı',
                'access_token' => $token,
                'token_expires_at' => isset($long['expires_in']) ? now()->addSeconds((int) $long['expires_in']) : null,
                'scopes' => $debug['scopes'] ?? [],
                'status' => 'connected',
                'connected_by' => $cached['user_id'],
                'health_checked_at' => now(),
            ],
        );

        return [
            'account' => $account->toPublicArray(),
            'pages' => $this->driver->listPages($token),
            'ad_accounts' => $this->driver->listAdAccounts($token),
        ];
    }

    public function connectSystemUser(Workspace $workspace, User $user, string $token, string $name): SocialAccount
    {
        if (! $workspace->system_user_enabled && ! $user->is_super_admin) {
            abort(403, 'System User bağlantısı bu çalışma alanı için kapalı.');
        }

        $debug = $this->driver->debugToken($token);

        return SocialAccount::query()->updateOrCreate(
            [
                'workspace_id' => $workspace->id,
                'platform' => 'meta_system_user',
                'external_id' => (string) ($debug['user_id'] ?? 'system'),
            ],
            [
                'name' => $name,
                'access_token' => $token,
                'scopes' => $debug['scopes'] ?? [],
                'status' => ($debug['is_valid'] ?? false) ? 'connected' : 'error',
                'health_error' => $debug['error'] ?? null,
                'health_checked_at' => now(),
                'connected_by' => $user->id,
            ],
        );
    }

    public function selectAssets(Workspace $workspace, User $user, array $pageIds, array $igIds, array $adAccountIds): array
    {
        $userAccount = SocialAccount::query()
            ->where('workspace_id', $workspace->id)
            ->whereIn('platform', ['meta_user', 'meta_system_user'])
            ->where('status', 'connected')
            ->latest('id')
            ->firstOrFail();

        $token = (string) $userAccount->access_token;
        $pages = collect($this->driver->listPages($token))->keyBy('id');
        $ads = collect($this->driver->listAdAccounts($token))->keyBy('id');
        $created = [];

        foreach ($pageIds as $pageId) {
            $page = $pages->get($pageId);
            if (! $page) {
                continue;
            }
            $this->limits->assert($workspace, 'social_accounts');
            $created[] = SocialAccount::query()->updateOrCreate(
                ['workspace_id' => $workspace->id, 'platform' => 'facebook_page', 'external_id' => (string) $page['id']],
                [
                    'name' => $page['name'] ?? 'Sayfa',
                    'access_token' => $page['access_token'] ?? $token,
                    'status' => 'connected',
                    'connected_by' => $user->id,
                    'parent_external_id' => $userAccount->external_id,
                ],
            )->toPublicArray();
        }

        foreach ($igIds as $igId) {
            $page = $pages->first(fn ($page) => ($page['instagram_business_account']['id'] ?? null) == $igId);
            if (! $page) {
                continue;
            }
            $ig = $page['instagram_business_account'];
            $this->limits->assert($workspace, 'social_accounts');
            $created[] = SocialAccount::query()->updateOrCreate(
                ['workspace_id' => $workspace->id, 'platform' => 'instagram', 'external_id' => (string) $ig['id']],
                [
                    'name' => $ig['name'] ?? $ig['username'] ?? 'Instagram',
                    'username' => $ig['username'] ?? null,
                    'access_token' => $page['access_token'] ?? $token,
                    'status' => 'connected',
                    'connected_by' => $user->id,
                    'parent_external_id' => (string) $page['id'],
                ],
            )->toPublicArray();
        }

        foreach ($adAccountIds as $adId) {
            $ad = $ads->get($adId);
            if (! $ad) {
                continue;
            }
            $this->limits->assert($workspace, 'ad_accounts');
            $social = SocialAccount::query()->updateOrCreate(
                ['workspace_id' => $workspace->id, 'platform' => 'meta_ad_account', 'external_id' => (string) $ad['id']],
                [
                    'name' => $ad['name'] ?? 'Reklam hesabı',
                    'access_token' => $token,
                    'status' => 'connected',
                    'connected_by' => $user->id,
                    'meta' => ['currency' => $ad['currency'] ?? null],
                ],
            );
            AdAccount::query()->updateOrCreate(
                ['workspace_id' => $workspace->id, 'external_id' => (string) $ad['id']],
                [
                    'social_account_id' => $social->id,
                    'name' => $ad['name'] ?? 'Reklam hesabı',
                    'currency' => $ad['currency'] ?? null,
                    'status' => 'active',
                ],
            );
            $created[] = $social->toPublicArray();
        }

        return $created;
    }

    public function withdraw(Workspace $workspace, User $user): void
    {
        $tokens = SocialAccount::query()
            ->where('workspace_id', $workspace->id)
            ->where('platform', 'meta_user')
            ->get()
            ->map(fn (SocialAccount $account) => (string) $account->access_token)
            ->filter(fn (string $token) => $token !== '')
            ->unique()
            ->values();

        foreach ($tokens as $token) {
            $this->driver->revokePermissions($token);
        }

        SocialAccount::query()
            ->where('workspace_id', $workspace->id)
            ->whereIn('platform', ['meta_user', 'facebook_page', 'instagram', 'meta_ad_account'])
            ->each(function (SocialAccount $account) {
                $account->forceFill([
                    'access_token' => null,
                    'status' => 'disconnected',
                ])->save();
            });

        $this->consents->withdraw($workspace, 'meta');

        $this->audit->log(
            $workspace->id,
            'user',
            $user->id,
            'meta.consent.withdrawn',
            'ok',
            null,
            'meta',
            null,
            null,
            null,
            ['provider' => 'meta'],
        );
    }

    public function redirectUri(): string
    {
        return rtrim((string) config('app.url'), '/').'/api/meta/callback';
    }

    private function cacheKey(string $state): string
    {
        return 'meta.connect.'.hash('sha256', $state);
    }

    /** @param  array<string, mixed>  $cached */
    private function assertCachedConsent(array $cached): void
    {
        $consentId = $cached['consent_id'] ?? null;
        if (! $consentId) {
            abort(422, 'Açık rıza olmadan bağlantı tamamlanamaz.');
        }

        $consent = Consent::query()->withoutGlobalScopes()->find($consentId);
        $current = $this->consents->textVersion('meta');
        $matches = $consent
            && $consent->provider === 'meta'
            && (int) $consent->workspace_id === (int) $cached['workspace_id']
            && (int) $consent->user_id === (int) $cached['user_id']
            && $consent->text_version === $current
            && $consent->withdrawn_at === null;

        if (! $matches) {
            abort(422, 'Açık rıza olmadan bağlantı tamamlanamaz.');
        }
    }
}
