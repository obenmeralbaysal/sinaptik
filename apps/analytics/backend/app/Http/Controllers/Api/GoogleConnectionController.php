<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Workspace;
use App\Services\Google\GoogleConnectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GoogleConnectionController extends Controller
{
    public function __construct(private GoogleConnectService $google) {}

    public function show(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->google->describe($this->user($request), $this->workspace($request)),
        ]);
    }

    public function start(Request $request): JsonResponse
    {
        $data = $request->validate([
            'consent' => ['required', 'accepted'],
            'text_version' => ['required', 'string', 'max:200'],
        ]);

        return response()->json([
            'data' => $this->google->start($this->user($request), $this->workspace($request), $data['text_version']),
        ]);
    }

    public function callback(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:2048'],
            'state' => ['required', 'string', 'max:200'],
        ]);

        return response()->json([
            'data' => $this->google->callback($this->user($request), $data['code'], $data['state']),
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->google->withdraw($this->user($request), $this->workspace($request)),
        ]);
    }

    private function user(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        return $user;
    }

    private function workspace(Request $request): Workspace
    {
        $workspace = $request->attributes->get('workspace');
        abort_unless($workspace instanceof Workspace, 400);

        return $workspace;
    }
}
