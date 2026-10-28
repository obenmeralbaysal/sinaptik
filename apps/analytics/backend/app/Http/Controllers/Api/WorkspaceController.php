<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkspaceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $workspaces = $request->user()->workspaces()->orderBy('name')->get();

        return response()->json([
            'data' => $workspaces->map(fn (Workspace $workspace) => [
                'id' => $workspace->id,
                'slug' => $workspace->slug,
                'name' => $workspace->name,
                'role' => $workspace->pivot->role,
            ])->values(),
        ]);
    }
}
