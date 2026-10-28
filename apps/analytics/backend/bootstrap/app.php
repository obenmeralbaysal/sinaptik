<?php

use App\Http\Middleware\EnforceSiteLimit;
use App\Http\Middleware\EnsureActiveSubscription;
use App\Http\Middleware\RequireMfaForPrivileged;
use App\Http\Middleware\ResolveWorkspace;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->statefulApi();

        $middleware->alias([
            'workspace' => ResolveWorkspace::class,
            'subscription' => EnsureActiveSubscription::class,
            'limit.sites' => EnforceSiteLimit::class,
            'mfa.privileged' => RequireMfaForPrivileged::class,
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (AuthenticationException $e, $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json(['message' => 'Kimlik doğrulanamadı.'], 401);
            }
        });
    })->create();
