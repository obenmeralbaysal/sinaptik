<?php

namespace App\Http\Middleware;

use App\Billing\LimitGuard;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class EnforceSiteLimit
{
    public function __construct(private readonly LimitGuard $limits) {}

    public function handle(Request $request, Closure $next): Response
    {
        $workspace = $request->attributes->get('workspace');
        if (! $workspace) {
            return response()->json(['message' => 'Çalışma alanı çözülemedi.'], 400);
        }

        try {
            $this->limits->ensureCanAddSite($workspace);
        } catch (HttpException $e) {
            $error = $e->getStatusCode() === 403 ? 'limit_exceeded' : 'subscription_required';

            return response()->json([
                'error' => $error,
                'message' => $e->getMessage(),
            ], $e->getStatusCode());
        }

        return $next($request);
    }
}
