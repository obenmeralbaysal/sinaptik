<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use App\Support\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOwnerTwoFactor
{
    public function handle(Request $request, Closure $next): Response
    {
        if (in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return $next($request);
        }

        $membership = $request->attributes->get('membership');
        $workspace = $request->attributes->get('workspace');
        $user = $request->user();
        if (! $membership || ! $user || $membership->role !== Role::OWNER) {
            return $next($request);
        }

        $required = (bool) ($workspace->require_owner_2fa ?? false) || (bool) Setting::getValue('security.owners_must_use_2fa', false);
        if ($required && $user->totp_confirmed_at === null && ! $request->is('api/two-factor/*')) {
            abort(403, 'Sahip hesabı için iki adımlı doğrulama zorunlu.');
        }

        return $next($request);
    }
}
