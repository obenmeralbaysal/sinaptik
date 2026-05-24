<?php

namespace App\Providers;

use App\Contracts\PaymentGateway;
use App\Models\User;
use App\Services\AI\AIManager;
use App\Services\Payments\NullGateway;
use App\Services\X\XClient;
use App\Services\X\XOAuthService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AIManager::class);

        $this->app->bind(PaymentGateway::class, NullGateway::class);

        $this->app->singleton(XOAuthService::class);
        $this->app->singleton(XClient::class);
    }

    public function boot(): void
    {
        Gate::define('access-admin', fn (User $user) => (bool) $user->is_super_admin);
    }
}
