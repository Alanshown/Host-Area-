<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Report;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function store(Request $request, $postId)
    {
        $post = Post::with('user:id')->findOrFail($postId);

        if ($request->user()->id === $post->user_id) {
            return response()->json(['message' => '不能举报自己的帖子'], 422);
        }

        $validated = $request->validate([
            'reason' => 'required|string|min:6|max:500',
        ]);

        $existing = Report::query()
            ->where('user_id', $request->user()->id)
            ->where('post_id', $post->id)
            ->whereIn('status', ['pending', 'in_review'])
            ->latest()
            ->first();

        if ($existing) {
            return response()->json([
                'message' => '你已经提交过该帖子的举报，管理员仍在处理中。',
                'data' => $existing,
            ], 422);
        }

        $report = Report::create([
            'user_id' => $request->user()->id,
            'post_id' => $post->id,
            'reason' => $validated['reason'],
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => '举报已提交，管理员会尽快处理。',
            'data' => $report->load(['user:id,username', 'post:id,title']),
        ], 201);
    }
}