<?php

namespace App\Providers;

use App\Services\Billing\BillingGateway;
use App\Services\Billing\FakeGateway;
use App\Services\Billing\SinappHubGateway;
use App\Services\Meta\FakeMetaDriver;
use App\Services\Meta\GraphMetaDriver;
use App\Services\Meta\MetaDriver;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(\App\Support\WorkspaceContext::class);

        $this->app->bind(BillingGateway::class, function () {
            return config('billing.gateway') === 'sinapp_hub'
                ? $this->app->make(SinappHubGateway::class)
                : $this->app->make(FakeGateway::class);
        });

        $this->app->bind(MetaDriver::class, function () {
            return config('services.meta.driver') === 'graph'
                ? $this->app->make(GraphMetaDriver::class)
                : $this->app->make(FakeMetaDriver::class);
        });
    }

    public function boot(): void
    {
        VerifyEmail::createUrlUsing(function ($notifiable) {
            return URL::temporarySignedRoute(
                'verification.verify',
                now()->addMinutes(60),
                [
                    'id' => $notifiable->getKey(),
                    'hash' => sha1($notifiable->getEmailForVerification()),
                ],
            );
        });

        ResetPassword::createUrlUsing(function ($user, string $token) {
            return rtrim((string) config('sinsocial.frontend_url'), '/')
                .'/sifre-sifirla?token='.$token.'&email='.urlencode($user->email);
        });
    }
}
