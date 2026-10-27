<?php

namespace App\Providers;

use App\Billing\SinappSubscriptionProvider;
use App\Contracts\Google\AnalyticsDataClient;
use App\Contracts\Google\GoogleOAuthClient;
use App\Contracts\Google\SearchConsoleClient;
use App\Contracts\IdentityProvider;
use App\Contracts\PaymentGateway;
use App\Contracts\SubscriptionProvider;
use App\Identity\LocalIdentityProvider;
use App\Identity\OidcIdentityProvider;
use App\Services\Google\NullAnalyticsDataClient;
use App\Services\Google\NullGoogleOAuthClient;
use App\Services\Google\NullSearchConsoleClient;
use App\Services\Payments\NullGateway;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(IdentityProvider::class, function ($app) {
            return match (config('identity.driver')) {
                'oidc' => $app->make(OidcIdentityProvider::class),
                'local' => $app->make(LocalIdentityProvider::class),
                default => throw new InvalidArgumentException('Unknown identity driver.'),
            };
        });

        $this->app->bind(SubscriptionProvider::class, SinappSubscriptionProvider::class);
        $this->app->bind(PaymentGateway::class, NullGateway::class);
        $this->app->bind(GoogleOAuthClient::class, NullGoogleOAuthClient::class);
        $this->app->bind(SearchConsoleClient::class, NullSearchConsoleClient::class);
        $this->app->bind(AnalyticsDataClient::class, NullAnalyticsDataClient::class);
    }

    public function boot(): void
    {
        //
    }
}
