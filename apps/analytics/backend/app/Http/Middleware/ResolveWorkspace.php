<?php

namespace App\Http\Middleware;

use App\Models\Workspace;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveWorkspace
{
    public function handle(Request $request, Closure $next): Response
    {
        $slug = (string) $request->header('X-Workspace-Slug', '');
        if ($slug === '') {
            return response()->json(['message' => 'Çalışma alanı başlığı gerekli.'], 400);
        }

        $workspace = Workspace::query()->where('slug', $slug)->first();
        if (! $workspace) {
            return response()->json(['message' => 'Çalışma alanı bulunamadı.'], 404);
        }

        $user = $request->user();
        if (! $user || ! $user->isMemberOf($workspace)) {
            return response()->json(['message' => 'Bu çalışma alanına üye değilsiniz.'], 403);
        }

        $request->attributes->set('workspace', $workspace);

        return $next($request);
    }
}
