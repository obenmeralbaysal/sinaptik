<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\SocialComment;
use App\Services\Comments\CommentService;
use App\Support\Role;
use Illuminate\Http\Request;
use RuntimeException;

class CommentController extends Controller
{
    public function inbox(Request $request, CommentService $comments)
    {
        return response()->json([
            'comments' => $comments->inbox()->map->toPublicArray()->values(),
        ]);
    }

    public function sync(Request $request, CommentService $comments)
    {
        $this->assertEditor($request);
        $synced = $comments->syncWorkspace();

        return response()->json([
            'synced_posts' => $synced,
            'comments' => $comments->inbox()->map->toPublicArray()->values(),
        ]);
    }

    public function forPost(Request $request, Post $post, CommentService $comments)
    {
        try {
            $rows = $comments->syncPost($post);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'comments' => $rows->map->toPublicArray()->values(),
        ]);
    }

    public function reply(Request $request, SocialComment $comment, CommentService $comments)
    {
        $this->assertEditor($request);
        $data = $request->validate([
            'message' => ['required', 'string', 'max:2200'],
        ]);

        try {
            $reply = $comments->reply($comment, $data['message']);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json(['comment' => $reply->toPublicArray()], 201);
    }

    public function hide(Request $request, SocialComment $comment, CommentService $comments)
    {
        $this->assertEditor($request);
        $request->validate([
            'hidden' => ['sometimes', 'boolean'],
        ]);

        try {
            $updated = $comments->hide($comment, $request->boolean('hidden', true));
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json(['comment' => $updated->toPublicArray()]);
    }

    public function destroy(Request $request, SocialComment $comment, CommentService $comments)
    {
        $this->assertEditor($request);

        try {
            $comments->delete($comment);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json(['deleted' => true]);
    }

    private function assertEditor(Request $request): void
    {
        if (! Role::canEditContent($request->attributes->get('membership')->role)) {
            abort(403);
        }
    }
}
