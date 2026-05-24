<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrganizationController extends Controller
{
    public function show(Request $request, string $orgSlug): JsonResponse
    {
        $org = app('currentOrganization');
        $org->loadMissing('plan', 'owner');

        return response()->json([
            'data' => [
                'id' => $org->id,
                'slug' => $org->slug,
                'name' => $org->name,
                'owner' => [
                    'id' => $org->owner->id,
                    'name' => $org->owner->name,
                    'email' => $org->owner->email,
                ],
                'plan' => $org->plan ? [
                    'id' => $org->plan->id,
                    'slug' => $org->plan->slug,
                    'name' => $org->plan->name,
                    'x_account_limit' => $org->plan->x_account_limit,
                    'daily_post_limit' => $org->plan->daily_post_limit,
                    'daily_reply_limit' => $org->plan->daily_reply_limit,
                ] : null,
                'settings' => $org->settings,
                'role' => $request->user()->roleIn($org),
            ],
        ]);
    }

    public function update(Request $request, string $orgSlug): JsonResponse
    {
        /** @var Organization $org */
        $org = app('currentOrganization');

        $role = $request->user()->roleIn($org);
        if (! in_array($role, ['owner', 'admin'])) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:120'],
            'settings' => ['sometimes', 'array'],
        ]);

        $org->update($data);

        return response()->json(['data' => $org->only('id', 'slug', 'name', 'settings')]);
    }

    public function members(Request $request, string $orgSlug): JsonResponse
    {
        $org = app('currentOrganization');
        $members = $org->users()->get()->map(fn ($u) => [
            'id' => $u->id,
            'name' => $u->name,
            'email' => $u->email,
            'role' => $u->pivot->role,
        ]);

        return response()->json(['data' => $members]);
    }

    public function showAiSettings(Request $request, string $orgSlug): JsonResponse
    {
        /** @var Organization $org */
        $org = app('currentOrganization');
        $this->ensureCanManage($request, $org);

        return response()->json(['data' => $this->presentAiSettings($org)]);
    }

    public function updateAiSettings(Request $request, string $orgSlug): JsonResponse
    {
        /** @var Organization $org */
        $org = app('currentOrganization');
        $this->ensureCanManage($request, $org);

        $data = $request->validate([
            'driver' => ['nullable', 'in:gemini,openai'],
            'gemini_api_key' => ['nullable', 'string', 'max:500'],
            'openai_api_key' => ['nullable', 'string', 'max:500'],
            'default_text_model' => ['nullable', 'string', 'max:80'],
            'default_image_model' => ['nullable', 'string', 'max:80'],
            'clear_gemini_key' => ['sometimes', 'boolean'],
            'clear_openai_key' => ['sometimes', 'boolean'],
        ]);

        if (array_key_exists('driver', $data)) {
            $org->ai_driver = $data['driver'];
        }
        if (! empty($data['gemini_api_key'])) {
            $org->ai_gemini_api_key = $data['gemini_api_key'];
        }
        if (! empty($data['openai_api_key'])) {
            $org->ai_openai_api_key = $data['openai_api_key'];
        }
        if (! empty($data['clear_gemini_key'])) {
            $org->ai_gemini_api_key = null;
        }
        if (! empty($data['clear_openai_key'])) {
            $org->ai_openai_api_key = null;
        }
        if (array_key_exists('default_text_model', $data)) {
            $org->ai_default_text_model = $data['default_text_model'];
        }
        if (array_key_exists('default_image_model', $data)) {
            $org->ai_default_image_model = $data['default_image_model'];
        }
        $org->save();

        return response()->json(['data' => $this->presentAiSettings($org->fresh())]);
    }

    private function presentAiSettings(Organization $org): array
    {
        return [
            'driver' => $org->ai_driver,
            'has_gemini_key' => filled($org->ai_gemini_api_key),
            'has_openai_key' => filled($org->ai_openai_api_key),
            'default_text_model' => $org->ai_default_text_model,
            'default_image_model' => $org->ai_default_image_model,
        ];
    }

    private function ensureCanManage(Request $request, Organization $org): void
    {
        $role = $request->user()->roleIn($org);
        if (! in_array($role, ['owner', 'admin'], true) && ! $request->user()->is_super_admin) {
            abort(403, 'Only owners or admins can manage AI settings.');
        }
    }
}
