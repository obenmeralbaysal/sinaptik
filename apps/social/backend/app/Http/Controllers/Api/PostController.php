<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\SocialAccount;
use App\Services\X\XClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PostController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Post::query()->with('socialAccount:id,username,provider')->orderByDesc('created_at');

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        return response()->json(['data' => $query->paginate(50)]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'social_account_id' => ['required', Rule::exists('social_accounts', 'id')],
            'content' => ['required', 'string', 'max:280'],
            'media_paths' => ['sometimes', 'array'],
            'scheduled_at' => ['nullable', 'date'],
            'status' => ['sometimes', Rule::in(['draft', 'pending'])],
        ]);

        $account = SocialAccount::findOrFail($data['social_account_id']);

        $post = Post::create([
            'social_account_id' => $account->id,
            'content' => $data['content'],
            'media_paths' => $data['media_paths'] ?? null,
            'scheduled_at' => $data['scheduled_at'] ?? null,
            'status' => $data['status'] ?? (isset($data['scheduled_at']) ? 'pending' : 'draft'),
        ]);

        return response()->json(['data' => $post], 201);
    }

    public function show(Post $post): JsonResponse
    {
        return response()->json(['data' => $post->load('socialAccount:id,username,provider')]);
    }

    public function update(Request $request, Post $post): JsonResponse
    {
        $data = $request->validate([
            'content' => ['sometimes', 'string', 'max:280'],
            'media_paths' => ['sometimes', 'array'],
            'scheduled_at' => ['nullable', 'date'],
            'status' => ['sometimes', Rule::in(['draft', 'pending'])],
        ]);

        $post->update($data);
        return response()->json(['data' => $post]);
    }

    public function destroy(Post $post): JsonResponse
    {
        $post->delete();
        return response()->json(['message' => 'Deleted.']);
    }

    public function sendNow(Post $post, XClient $x): JsonResponse
    {
        try {
            $result = $x->postContent($post->socialAccount, $post->content);
            $post->update([
                'status' => 'posted',
                'posted_at' => now(),
                'provider_post_id' => $result['id'] ?? null,
                'error' => null,
            ]);
        } catch (\Throwable $e) {
            $post->update(['status' => 'failed', 'error' => $e->getMessage()]);
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['data' => $post->fresh()]);
    }

    public function bulkSchedule(Request $request): JsonResponse
    {
        $data = $request->validate([
            'post_ids' => ['required', 'array', 'min:1'],
            'post_ids.*' => ['integer'],
            'start_at' => ['required', 'date'],
            'interval_minutes' => ['required', 'integer', 'min:1'],
        ]);

        $posts = Post::whereIn('id', $data['post_ids'])->get();
        $start = \Carbon\Carbon::parse($data['start_at']);

        foreach ($posts as $i => $p) {
            $p->update([
                'status' => 'pending',
                'scheduled_at' => $start->copy()->addMinutes($data['interval_minutes'] * $i),
            ]);
        }

        return response()->json(['data' => $posts->fresh()]);
    }
}
