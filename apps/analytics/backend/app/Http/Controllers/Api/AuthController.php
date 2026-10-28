<?php

namespace App\Http\Controllers\Api;

use App\Contracts\IdentityProvider;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Identity\LocalIdentityProvider;
use App\Identity\Oidc\OidcException;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(private readonly IdentityProvider $identity) {}

    public function register(Request $request): JsonResponse
    {
        if (! $this->identity instanceof LocalIdentityProvider) {
            return response()->json(['message' => 'Kayıt yalnızca yerel kimlik sürücüsünde açıktır.'], 501);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:191', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'workspace_name' => ['nullable', 'string', 'max:120'],
        ]);

        $user = $this->identity->register([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'workspace_name' => $data['workspace_name'] ?? null,
        ]);

        $request->session()->regenerate();

        return response()->json([
            'user' => new UserResource($user->loadMissing('workspaces', 'currentWorkspace')),
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        if (! $this->identity instanceof LocalIdentityProvider) {
            return response()->json([
                'message' => 'Parola ile giriş bu kimlik sürücüsünde kapalı. /auth/login kullanın.',
            ], 501);
        }

        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['sometimes', 'boolean'],
        ]);

        try {
            $user = $this->identity->authenticate($data);
        } catch (OidcException $e) {
            return response()->json(['message' => $e->getMessage()], 501);
        } catch (ValidationException $e) {
            throw $e;
        }

        $request->session()->regenerate();

        return response()->json([
            'user' => new UserResource($user->loadMissing('workspaces', 'currentWorkspace')),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->identity->logout();

        return response()->json(['message' => 'Çıkış yapıldı.']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => new UserResource($request->user()->loadMissing('workspaces', 'currentWorkspace')),
        ]);
    }

    public function switchWorkspace(Request $request): JsonResponse
    {
        $data = $request->validate([
            'slug' => ['required', 'string', Rule::exists('workspaces', 'slug')],
        ]);

        $user = $request->user();
        $workspace = Workspace::query()->where('slug', $data['slug'])->firstOrFail();

        if (! $user->isMemberOf($workspace)) {
            return response()->json(['message' => 'Bu çalışma alanına üye değilsiniz.'], 403);
        }

        $user->forceFill(['current_workspace_id' => $workspace->id])->save();

        return response()->json([
            'user' => new UserResource($user->fresh(['workspaces', 'currentWorkspace'])),
        ]);
    }
}
