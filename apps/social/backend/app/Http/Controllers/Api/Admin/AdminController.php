<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\Post;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    public function stats(): JsonResponse
    {
        return response()->json([
            'data' => [
                'users' => User::count(),
                'organizations' => Organization::count(),
                'social_accounts' => SocialAccount::withoutGlobalScopes()->count(),
                'posts_total' => Post::withoutGlobalScopes()->count(),
                'posts_pending' => Post::withoutGlobalScopes()->where('status', 'pending')->count(),
                'posts_failed' => Post::withoutGlobalScopes()->where('status', 'failed')->count(),
            ],
        ]);
    }

    public function users(): JsonResponse
    {
        return response()->json([
            'data' => User::query()->withCount('organizations')->orderByDesc('created_at')->paginate(50),
        ]);
    }

    public function plans(): JsonResponse
    {
        return response()->json(['data' => Plan::orderBy('price_cents')->get()]);
    }

    public function storePlan(Request $request): JsonResponse
    {
        $data = $request->validate($this->planRules());
        $plan = Plan::create($data);
        return response()->json(['data' => $plan], 201);
    }

    public function updatePlan(Request $request, Plan $plan): JsonResponse
    {
        $data = $request->validate($this->planRules($plan->id));
        $plan->update($data);
        return response()->json(['data' => $plan]);
    }

    public function destroyPlan(Plan $plan): JsonResponse
    {
        $plan->delete();
        return response()->json(['message' => 'Deleted.']);
    }

    private function planRules(?int $ignoreId = null): array
    {
        return [
            'slug' => ['required', 'string', 'max:50', Rule::unique('plans', 'slug')->ignore($ignoreId)],
            'name' => ['required', 'string', 'max:50'],
            'price_cents' => ['required', 'integer', 'min:0'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'x_account_limit' => ['required', 'integer', 'min:0'],
            'daily_post_limit' => ['required', 'integer', 'min:0'],
            'daily_reply_limit' => ['required', 'integer', 'min:0'],
            'features' => ['nullable', 'array'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
