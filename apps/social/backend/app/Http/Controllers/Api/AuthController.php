<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:191', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'organization_name' => ['nullable', 'string', 'max:120'],
        ]);

        $user = DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
            ]);

            $orgName = $data['organization_name'] ?? ($data['name']."'s Workspace");
            $org = Organization::create([
                'slug' => $this->generateUniqueSlug($data['name']),
                'name' => $orgName,
                'owner_id' => $user->id,
            ]);

            $org->users()->attach($user->id, ['role' => 'owner']);
            $user->forceFill(['current_organization_id' => $org->id])->save();

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        return response()->json([
            'user' => new UserResource($user->fresh('organizations', 'currentOrganization')),
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['sometimes', 'boolean'],
        ]);

        if (! Auth::attempt(
            ['email' => $data['email'], 'password' => $data['password']],
            $data['remember'] ?? false
        )) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $request->session()->regenerate();

        return response()->json([
            'user' => new UserResource(Auth::user()->loadMissing('organizations', 'currentOrganization')),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Logged out.']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => new UserResource($request->user()->loadMissing('organizations', 'currentOrganization')),
        ]);
    }

    public function switchOrganization(Request $request): JsonResponse
    {
        $data = $request->validate([
            'slug' => ['required', 'string', Rule::exists('organizations', 'slug')],
        ]);

        $user = $request->user();
        $org = Organization::where('slug', $data['slug'])->firstOrFail();

        if (! $user->isMemberOf($org)) {
            return response()->json(['message' => 'Not a member of this organization.'], 403);
        }

        $user->forceFill(['current_organization_id' => $org->id])->save();

        return response()->json(['user' => new UserResource($user->fresh('organizations', 'currentOrganization'))]);
    }

    private function generateUniqueSlug(string $base): string
    {
        $slug = Str::slug($base) ?: 'org';
        $candidate = $slug;
        $i = 1;
        while (Organization::where('slug', $candidate)->exists()) {
            $candidate = $slug.'-'.$i++;
        }
        return $candidate;
    }
}
