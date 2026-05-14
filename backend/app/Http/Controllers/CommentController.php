<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Post;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function index($postId)
    {
        $comments = Comment::where('post_id', $postId)
            ->with([
                'user:id,username,avatar',
                'replies.user:id,username,avatar',
            ])
            ->whereNull('parent_id')
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json(['data' => $comments]);
    }

    public function store(Request $request, $postId)
    {
        $validated = $request->validate([
            'content'   => 'required|string|min:1|max:1000',
            'parent_id' => 'nullable|integer|exists:comments,id',
        ]);

        Post::query()->findOrFail($postId);

        if (! empty($validated['parent_id'])) {
            $parentComment = Comment::query()->findOrFail($validated['parent_id']);

            if ((int) $parentComment->post_id !== (int) $postId) {
                return response()->json(['message' => '回复的目标评论不属于当前帖子'], 422);
            }
        }

        $validated['post_id'] = (int) $postId;
        $validated['user_id'] = $request->user()->id;
        $comment = Comment::create($validated);
        $comment->load('user:id,username,avatar');

        return response()->json(['data' => $comment], 201);
    }

    public function destroy(Request $request, $id)
    {
        $comment = Comment::findOrFail($id);
        if ($comment->user_id !== $request->user()->id && $request->user()->role !== 'admin') {
            return response()->json(['message' => '无权限删除此评论'], 403);
        }
        $comment->delete();
        return response()->json(['message' => '已删除']);
    }
}
