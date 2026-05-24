<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Target;
use App\Services\AI\AIManager;
use App\Services\X\XClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TargetController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => Target::query()->with('socialAccount:id,username,provider')->latest()->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'social_account_id' => ['nullable', Rule::exists('social_accounts', 'id')],
            'platform' => ['sometimes', Rule::in(['x', 'instagram', 'facebook'])],
            'target_username' => ['required', 'string', 'max:100'],
            'ai_tone' => ['sometimes', 'string', 'max:30'],
            'custom_prompt' => ['nullable', 'string', 'max:1000'],
            'ai_model' => ['nullable', 'string'],
            'daily_limit' => ['sometimes', 'integer', 'min:0', 'max:1000'],
            'reply_frequency_minutes' => ['sometimes', 'integer', 'min:1'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $target = Target::create($data);
        return response()->json(['data' => $target], 201);
    }

    public function show(Target $target): JsonResponse
    {
        return response()->json(['data' => $target]);
    }

    public function update(Request $request, Target $target): JsonResponse
    {
        $data = $request->validate([
            'social_account_id' => ['nullable', Rule::exists('social_accounts', 'id')],
            'target_username' => ['sometimes', 'string', 'max:100'],
            'ai_tone' => ['sometimes', 'string', 'max:30'],
            'custom_prompt' => ['nullable', 'string', 'max:1000'],
            'ai_model' => ['nullable', 'string'],
            'daily_limit' => ['sometimes', 'integer', 'min:0', 'max:1000'],
            'reply_frequency_minutes' => ['sometimes', 'integer', 'min:1'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
        $target->update($data);
        return response()->json(['data' => $target]);
    }

    public function destroy(Target $target): JsonResponse
    {
        $target->delete();
        return response()->json(['message' => 'Deleted.']);
    }

    public function preview(Target $target, XClient $x, AIManager $aiManager): JsonResponse
    {
        $account = $target->socialAccount;
        if (! $account) {
            return response()->json(['message' => 'Target has no associated social account. Set one to preview.'], 422);
        }

        $tweets = $x->fetchUserTimeline($account, $target->target_username, 1);
        if (empty($tweets)) {
            return response()->json(['message' => 'No recent tweets found.'], 404);
        }

        $original = $tweets[0]['text'];
        $ai = $aiManager->forCurrentOrganization();
        $reply = $ai->generateReply($original, $target->ai_tone, $target->custom_prompt, $target->ai_model);

        return response()->json([
            'original' => $original,
            'reply' => $reply,
        ]);
    }
}
