<?php

namespace App\Http\Middleware;

use App\Identity\MfaAssurance;
use App\Models\User;
use App\Models\Workspace;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireMfaForPrivileged
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user instanceof User) {
            return response()->json(['message' => 'Kimlik doğrulanamadı.'], 401);
        }

        if ($this->privileged($user, $request) && ! MfaAssurance::satisfied()) {
            return response()->json([
                'message' => 'Owner ve admin işlemleri için çok faktörlü doğrulama gerekir.',
                'error' => 'mfa_required',
            ], 403);
        }

        return $next($request);
    }

    private function privileged(User $user, Request $request): bool
    {
        $workspace = $request->attributes->get('workspace');
        $query = $user->workspaces()->wherePivotIn('role', ['owner', 'admin']);
        if ($workspace instanceof Workspace) {
            $query->where('workspaces.id', $workspace->id);
        }

        return $query->exists();
    }
}
