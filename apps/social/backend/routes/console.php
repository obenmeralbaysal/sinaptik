<?php

use App\Jobs\CheckMetaTokenHealth;
use App\Jobs\PublishPost;
use App\Jobs\SyncAds;
use App\Jobs\SyncInsights;
use App\Models\AdAccount;
use App\Models\Post;
use App\Models\SocialAccount;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Workspace;
use App\Notifications\WorkspaceAlert;
use App\Services\TrialService;
use App\Support\WorkspaceContext;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('sinsocial:admin', function () {
    $email = (string) env('ADMIN_EMAIL');
    $password = (string) env('ADMIN_PASSWORD');
    if ($email === '' || $password === '') {
        $this->error('ADMIN_EMAIL ve ADMIN_PASSWORD gerekli.');

        return 1;
    }

    $user = User::query()->updateOrCreate(
        ['email' => $email],
        [
            'name' => env('ADMIN_NAME', 'Sinaptik Admin'),
            'password' => $password,
            'email_verified_at' => now(),
            'is_super_admin' => true,
            'locale' => 'tr',
        ],
    );
    $this->info('Süper admin hazır: '.$user->email);

    return 0;
})->purpose('Create or update the Filament super admin');

Schedule::call(function () {
    app(WorkspaceContext::class)->activate(null, true);
    Subscription::query()->with('workspace')->whereIn('status', ['trialing', 'canceled', 'past_due'])->each(function (Subscription $subscription) {
        if ($subscription->workspace) {
            app(WorkspaceContext::class)->activate($subscription->workspace_id);
            app(TrialService::class)->sync($subscription->workspace);
        }
    });
})->hourly()->name('billing-sync');

Schedule::call(function () {
    app(WorkspaceContext::class)->activate(null, true);
    Workspace::query()
        ->whereNotNull('trial_lapsed_at')
        ->whereNull('deletion_scheduled_at')
        ->where('status', 'read_only')
        ->where('trial_lapsed_at', '<=', now()->subDays((int) config('sinsocial.lapsed_purge_days')))
        ->each(function (Workspace $workspace) {
            $workspace->update(['deletion_scheduled_at' => now()->addDays(7)]);
            $workspace->owner?->notify(new WorkspaceAlert(
                'Veriler silinecek',
                'Ödemesiz deneme süresi dolduğu için çalışma alanı verileri 7 gün içinde silinmek üzere işaretlendi.',
            ));
        });
})->daily()->name('flag-lapsed-workspaces');

Schedule::call(function () {
    app(WorkspaceContext::class)->activate(null, true);
    Workspace::query()->whereNotNull('deletion_scheduled_at')->where('deletion_scheduled_at', '<=', now())->each(function (Workspace $workspace) {
        $workspace->delete();
    });
})->daily()->name('purge-workspaces');

Schedule::call(function () {
    app(WorkspaceContext::class)->activate(null, true);
    Post::query()->where('status', 'scheduled')->where('scheduled_at', '<=', now())->each(function (Post $post) {
        $post->update(['status' => 'publishing']);
        PublishPost::dispatch($post->id, $post->workspace_id);
    });
})->everyMinute()->name('sweep-scheduled-posts');

Schedule::call(function () {
    app(WorkspaceContext::class)->activate(null, true);
    SocialAccount::query()->where('status', 'connected')->each(function (SocialAccount $account) {
        CheckMetaTokenHealth::dispatch($account->id, $account->workspace_id);
        if (in_array($account->platform, ['facebook_page', 'instagram'], true)) {
            SyncInsights::dispatch($account->id, $account->workspace_id);
        }
    });
    AdAccount::query()->each(fn (AdAccount $account) => SyncAds::dispatch($account->id, $account->workspace_id));
})->daily()->name('meta-health-and-sync');
