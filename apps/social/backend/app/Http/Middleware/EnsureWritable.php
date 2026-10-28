<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureWritable
{
    public function handle(Request $request, Closure $next): Response
    {
        if (in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return $next($request);
        }

        $workspace = $request->attributes->get('workspace');
        if ($workspace && $workspace->isReadOnly() && ! $this->allowed($request)) {
            abort(423, 'Çalışma alanı salt okunur. Ödeme veya silme iptali dışında değişiklik yapılamaz.');
        }

        return $next($request);
    }

    private function allowed(Request $request): bool
    {
        return $request->is('api/billing/*')
            || $request->is('api/two-factor/*')
            || $request->is('api/workspaces/*/cancel-deletion')
            || $request->is('api/logout');
    }
}
