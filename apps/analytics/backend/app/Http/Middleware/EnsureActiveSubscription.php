<?php

namespace App\Http\Middleware;

use App\Billing\LimitGuard;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class EnsureActiveSubscription
{
    public function __construct(private readonly LimitGuard $limits) {}

    public function handle(Request $request, Closure $next): Response
    {
        $workspace = $request->attributes->get('workspace');
        if (! $workspace) {
            return response()->json(['message' => 'Çalışma alanı çözülemedi.'], 400);
        }

        try {
            $this->limits->ensureActive($workspace);
        } catch (HttpException $e) {
            return response()->json([
                'error' => 'subscription_required',
                'message' => $e->getMessage(),
            ], $e->getStatusCode());
        }

        return $next($request);
    }
}
