<?php

namespace App\Jobs;

use App\Jobs\Middleware\SetWorkspaceContext;
use App\Models\SocialAccount;
use App\Models\User;
use App\Notifications\WorkspaceAlert;
use App\Services\Meta\MetaDriver;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CheckMetaTokenHealth implements ShouldQueue
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

    public function handle(MetaDriver $meta): void
    {
        $account = SocialAccount::query()->find($this->socialAccountId);
        if (! $account || ! $account->access_token) {
            return;
        }

        $debug = $meta->debugToken($account->access_token);
        $expires = isset($debug['expires_at']) && $debug['expires_at'] ? now()->createFromTimestamp((int) $debug['expires_at']) : $account->token_expires_at;
        $status = ($debug['is_valid'] ?? false) ? 'connected' : 'disconnected';
        if ($status === 'connected' && $expires && $expires->isBefore(now()->addDays(7))) {
            $status = 'expired';
        }

        $account->update([
            'status' => $status === 'expired' ? 'connected' : $status,
            'health_checked_at' => now(),
            'health_error' => $debug['error'] ?? ($status === 'expired' ? 'Token süresi dolmak üzere.' : null),
            'token_expires_at' => $expires,
        ]);

        if ($status !== 'connected') {
            User::query()->where('id', $account->connected_by)->first()
                ?->notify(new WorkspaceAlert('Meta bağlantısı yenilenmeli', $account->name.' hesabının yeniden bağlanması gerekiyor.'));
        }
    }
}
