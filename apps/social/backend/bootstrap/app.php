<?php

use App\Http\Middleware\ApplyRlsBypass;
use App\Http\Middleware\ApplyWorkspaceContext;
use App\Http\Middleware\EnsureOwnerTwoFactor;
use App\Http\Middleware\EnsureWritable;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->statefulApi();
        $middleware->alias([
            'workspace' => ApplyWorkspaceContext::class,
            'writable' => EnsureWritable::class,
            'owner.2fa' => EnsureOwnerTwoFactor::class,
            'rls.bypass' => ApplyRlsBypass::class,
        ]);
        $middleware->prependToPriorityList(SubstituteBindings::class, ApplyWorkspaceContext::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->is('mcp') || $request->expectsJson(),
        );
    })->create();
