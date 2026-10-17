<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AccountInsight;
use App\Models\MediaAsset;
use App\Models\Post;
use App\Models\PostInsight;
use App\Services\MediaLibrary;
use App\Services\Publishing\PostService;
use App\Support\Role;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function index(Request $request)
    {
        $query = Post::query()->with('account')->orderByDesc('id');
        if ($request->filled('from')) {
            $query->where('scheduled_at', '>=', $request->date('from'));
        }
        if ($request->filled('to')) {
            $query->where('scheduled_at', '<=', $request->date('to'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return response()->json(['posts' => $query->limit(200)->get()]);
    }

    public function store(Request $request, PostService $posts)
    {
        $membership = $request->attributes->get('membership');
        if (! Role::canEditContent($membership->role)) {
            abort(403);
        }
        $data = $request->validate([
            'body' => ['nullable', 'string'],
            'post_type' => ['nullable', 'in:feed,photo,carousel,video,reel,story'],
            'social_account_id' => ['nullable', 'integer'],
            'brand_id' => ['nullable', 'integer'],
            'media_ids' => ['array'],
            'scheduled_at' => ['nullable', 'date'],
            'status' => ['nullable', 'in:draft,scheduled,publishing'],
            'platform_options' => ['nullable', 'array'],
        ]);
        $post = $posts->create($request->attributes->get('workspace'), $request->user(), $data);

        return response()->json(['post' => $post], 201);
    }

    public function approve(Request $request, Post $post, PostService $posts)
    {
        if (! Role::canApprove($request->attributes->get('membership')->role)) {
            abort(403);
        }
        $data = $request->validate(['comment' => ['nullable', 'string']]);

        return response()->json(['post' => $posts->approve($post, $request->user(), $data['comment'] ?? null)]);
    }

    public function reject(Request $request, Post $post, PostService $posts)
    {
        if (! Role::canApprove($request->attributes->get('membership')->role)) {
            abort(403);
        }
        $data = $request->validate(['comment' => ['nullable', 'string']]);

        return response()->json(['post' => $posts->reject($post, $request->user(), $data['comment'] ?? null)]);
    }

    public function media(Request $request)
    {
        return response()->json(['media' => MediaAsset::query()->latest('id')->limit(200)->get()]);
    }

    public function upload(Request $request, MediaLibrary $library)
    {
        if (! Role::canEditContent($request->attributes->get('membership')->role)) {
            abort(403);
        }
        $request->validate([
            'file' => ['required', 'file', 'max:102400'],
            'brand_id' => ['nullable', 'integer'],
        ]);
        $asset = $library->store($request->attributes->get('workspace'), $request->user(), $request->file('file'), $request->integer('brand_id') ?: null);

        return response()->json(['media' => $asset, 'url' => $library->signedUrl($asset)], 201);
    }

    public function publicFile(Request $request, int $media, MediaLibrary $library)
    {
        if (! $request->hasValidSignature()) {
            abort(403);
        }
        $asset = MediaAsset::query()->withoutGlobalScopes()->findOrFail($media);

        return $library->stream($asset);
    }

    public function insights(Request $request)
    {
        return response()->json([
            'accounts' => AccountInsight::query()->latest('metric_date')->limit(60)->get(),
            'posts' => PostInsight::query()->latest('synced_at')->limit(60)->get(),
        ]);
    }
}
