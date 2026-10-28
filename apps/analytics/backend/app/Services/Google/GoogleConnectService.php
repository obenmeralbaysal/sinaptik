<?php

namespace App\Services\Google;

use App\Consent\GoogleConnectConsent;
use App\Contracts\Google\AnalyticsDataClient;
use App\Contracts\Google\GoogleOAuthClient;
use App\Models\AuditLog;
use App\Models\Consent;
use App\Models\GoogleConnection;
use App\Models\GoogleSyncJob;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class GoogleConnectService
{
    public function __construct(
        private GoogleOAuthClient $oauth,
        private GoogleTokenRevoker $revoker,
        private AnalyticsDataClient $analytics,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function describe(User $user, Workspace $workspace): array
    {
        $version = $this->version();
        $connection = $this->connection($workspace);
        $active = $this->activeConsent($connection?->user_id ?? $user->id, $workspace, $version);
        $connected = $connection?->status === 'connected';
        $needsReconsent = $connected && ($active === null || $connection->text_version !== $version);

        return [
            'provider' => 'google',
            'text' => GoogleConnectConsent::TEXT,
            'link_label' => GoogleConnectConsent::LINK_LABEL,
            'text_version' => $version,
            'notice_path' => (string) config('consent.google.notice_path'),
            'status' => $connection->status ?? 'disconnected',
            'sync_status' => $connection->sync_status ?? 'stopped',
            'connected' => $connected,
            'needs_reconsent' => $needsReconsent,
        ];
    }

    /**
     * @return array{url: string, state: string}
     */
    public function start(User $user, Workspace $workspace, string $textVersion): array
    {
        $version = $this->version();
        if ($textVersion !== $version) {
            abort(response()->json([
                'message' => 'Açık rıza metni güncel değil.',
                'error' => 'consent_required',
            ], 422));
        }

        $consent = Consent::query()->create([
            'user_id' => $user->id,
            'workspace_id' => $workspace->id,
            'provider' => 'google',
            'text_version' => $version,
            'granted_at' => now(),
            'withdrawn_at' => null,
        ]);

        $state = Str::random(40);
        Cache::put($this->stateKey($state), [
            'user_id' => $user->id,
            'workspace_id' => $workspace->id,
            'text_version' => $version,
            'consent_id' => $consent->id,
        ], now()->addMinutes(10));

        $this->audit($user, $workspace, 'google.consent.granted', [
            'provider' => 'google',
            'text_version' => $version,
        ]);

        return $this->oauth->authorizationUrl($state);
    }

    /**
     * @return array<string, mixed>
     */
    public function callback(User $user, string $code, string $state): array
    {
        $payload = Cache::pull($this->stateKey($state));
        if (! is_array($payload) || (int) ($payload['user_id'] ?? 0) !== $user->id) {
            abort(response()->json([
                'message' => 'Google bağlantısı için güncel açık rıza gerekli.',
                'error' => 'consent_required',
            ], 422));
        }

        $workspace = Workspace::query()->find($payload['workspace_id'] ?? 0);
        if (! $workspace instanceof Workspace || ! $user->isMemberOf($workspace)) {
            abort(response()->json([
                'message' => 'Google bağlantısı için güncel açık rıza gerekli.',
                'error' => 'consent_required',
            ], 422));
        }

        $version = $this->version();
        $consent = $this->activeConsent($user->id, $workspace, $version);
        if (
            $consent === null
            || (int) $consent->id !== (int) ($payload['consent_id'] ?? 0)
            || ($payload['text_version'] ?? null) !== $version
        ) {
            abort(response()->json([
                'message' => 'Google bağlantısı için güncel açık rıza gerekli.',
                'error' => 'consent_required',
            ], 422));
        }

        $tokens = $this->oauth->exchangeCode($code);
        $access = $tokens['access_token'] ?? null;
        $refresh = $tokens['refresh_token'] ?? null;
        if (! is_string($access) || $access === '') {
            throw new RuntimeException('Google jetonu alınamadı.');
        }

        $connection = GoogleConnection::query()->firstOrNew([
            'workspace_id' => $workspace->id,
        ]);
        $connection->fill([
            'user_id' => $user->id,
            'status' => 'connected',
            'access_token' => $access,
            'refresh_token' => is_string($refresh) && $refresh !== '' ? $refresh : null,
            'text_version' => $version,
            'sync_status' => 'active',
            'disconnected_at' => null,
        ]);
        $connection->save();

        GoogleSyncJob::query()
            ->where('google_connection_id', $connection->id)
            ->where('status', 'paused')
            ->update(['status' => 'pending']);

        $connection->syncJobs()->create([
            'workspace_id' => $workspace->id,
            'status' => 'pending',
        ]);

        $this->audit($user, $workspace, 'google.connected', [
            'provider' => 'google',
            'text_version' => $version,
        ]);

        return $this->describe($user, $workspace);
    }

    /**
     * @return array<string, mixed>
     */
    public function withdraw(User $user, Workspace $workspace): array
    {
        $connection = $this->connection($workspace);
        $token = $connection?->refresh_token ?: $connection?->access_token;
        $revokeError = null;

        if (is_string($token) && $token !== '') {
            try {
                $this->revoker->revoke($token);
            } catch (\Throwable $exception) {
                $revokeError = $exception;
            }
        }

        DB::transaction(function () use ($user, $workspace, $connection) {
            if ($connection instanceof GoogleConnection) {
                $connection->forceFill([
                    'access_token' => null,
                    'refresh_token' => null,
                    'status' => 'disconnected',
                    'sync_status' => 'stopped',
                    'disconnected_at' => now(),
                ])->save();

                GoogleSyncJob::query()
                    ->where('google_connection_id', $connection->id)
                    ->whereIn('status', ['pending', 'running', 'paused'])
                    ->update(['status' => 'cancelled']);
            }

            Consent::query()
                ->where('workspace_id', $workspace->id)
                ->where('provider', 'google')
                ->whereNull('withdrawn_at')
                ->update(['withdrawn_at' => now()]);

            $this->audit($user, $workspace, 'google.consent.withdrawn', [
                'provider' => 'google',
                'tokens_deleted' => true,
            ]);
        });

        if ($revokeError instanceof \Throwable) {
            throw $revokeError;
        }

        return $this->describe($user, $workspace);
    }

    public function runSync(Workspace $workspace): string
    {
        $connection = $this->connection($workspace);
        if (! $connection || $connection->status !== 'connected') {
            return 'stopped';
        }

        $version = $this->version();
        $active = $this->activeConsent($connection->user_id, $workspace, $version);
        if ($active === null || $connection->text_version !== $version) {
            $connection->forceFill(['sync_status' => 'paused'])->save();
            GoogleSyncJob::query()
                ->where('google_connection_id', $connection->id)
                ->whereIn('status', ['pending', 'running'])
                ->update(['status' => 'paused']);
            $actor = $connection->user;
            $this->audit($actor instanceof User ? $actor : null, $workspace, 'google.sync.paused', [
                'provider' => 'google',
                'text_version' => $version,
            ]);

            return 'paused';
        }

        $token = $connection->access_token;
        if (! is_string($token) || $token === '') {
            $connection->forceFill(['sync_status' => 'paused'])->save();

            return 'paused';
        }

        $this->analytics->runReport($token, 'properties/0', []);
        GoogleSyncJob::query()
            ->where('google_connection_id', $connection->id)
            ->where('status', 'pending')
            ->update(['status' => 'completed']);
        $connection->forceFill(['sync_status' => 'active'])->save();

        return 'active';
    }

    private function version(): string
    {
        return (string) config('consent.google.text_version');
    }

    private function connection(Workspace $workspace): ?GoogleConnection
    {
        return GoogleConnection::query()->where('workspace_id', $workspace->id)->first();
    }

    private function activeConsent(int $userId, Workspace $workspace, string $version): ?Consent
    {
        return Consent::query()
            ->where('user_id', $userId)
            ->where('workspace_id', $workspace->id)
            ->where('provider', 'google')
            ->where('text_version', $version)
            ->whereNull('withdrawn_at')
            ->latest('id')
            ->first();
    }

    private function stateKey(string $state): string
    {
        return 'google.oauth.'.hash('sha256', $state);
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function audit(?User $user, Workspace $workspace, string $action, array $metadata): void
    {
        AuditLog::query()->create([
            'user_id' => $user?->id,
            'workspace_id' => $workspace->id,
            'action' => $action,
            'metadata' => $metadata,
            'created_at' => now(),
        ]);
    }
}
