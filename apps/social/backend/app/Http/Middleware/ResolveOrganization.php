<?php

namespace App\Http\Middleware;

use App\Models\Organization;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveOrganization
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $slug = $request->route('orgSlug')
            ?? $request->header('X-Organization-Slug')
            ?? $user->currentOrganization?->slug;

        if (! $slug) {
            return response()->json(['message' => 'Organization context missing. Provide X-Organization-Slug header.'], 400);
        }

        $org = Organization::where('slug', $slug)->first();

        if (! $org) {
            return response()->json(['message' => 'Organization not found.'], 404);
        }

        if (! $user->is_super_admin && ! $user->isMemberOf($org)) {
            return response()->json(['message' => 'You are not a member of this organization.'], 403);
        }

        app()->instance('currentOrganization', $org);

        return $next($request);
    }
}
