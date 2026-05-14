<?php

namespace App\Http\Controllers;

use App\Models\Like;
use App\Models\Post;
use App\Services\RecommendationService;
use Illuminate\Http\Request;

class LikeController extends Controller
{
    protected $recommendationService;

    public function __construct(RecommendationService $recommendationService)
    {
        $this->recommendationService = $recommendationService;
    }

    public function toggle(Request $request, $postId)
    {
        $userId = $request->user()->id;
        $post = Post::findOrFail($postId);

        $existing = Like::where('user_id', $userId)->where('post_id', $postId)->first();

        if ($existing) {
            $existing->delete();
            if ($post->likes > 0) {
                $post->decrement('likes');
            }

            return response()->json(['liked' => false, 'likes' => max(0, $post->fresh()->likes)]);
        }

        Like::create(['user_id' => $userId, 'post_id' => $postId]);
        $post->increment('likes');

        // Track interaction for recommendations
        $this->recommendationService->trackInteraction(
            $userId,
            (string) $post->category_id,
            'like',
            $this->extractTags($post->title . ' ' . $post->content)
        );

        return response()->json(['liked' => true, 'likes' => $post->fresh()->likes]);
    }

    public function check(Request $request, $postId)
    {
        $liked = Like::where('user_id', $request->user()->id)
            ->where('post_id', $postId)
            ->exists();
        return response()->json(['liked' => $liked]);
    }

    public function batchCheck(Request $request)
    {
        $ids = $request->query('ids', '');
        $postIds = array_filter(explode(',', $ids), fn($v) => is_numeric($v));
        if (empty($postIds)) return response()->json(['data' => []]);

        $liked = Like::where('user_id', $request->user()->id)
            ->whereIn('post_id', $postIds)
            ->pluck('post_id')
            ->map(fn($v) => (int)$v)
            ->all();

        return response()->json(['data' => $liked]);
    }

    private function extractTags(string $content): array
    {
        $keywords = ['Vue', 'React', 'Laravel', 'Python', 'JavaScript', 'TypeScript',
                      'Docker', 'Kubernetes', 'AI', 'Machine Learning', 'DevOps', 'API'];

        $tags = [];
        foreach ($keywords as $keyword) {
            if (mb_stripos($content, $keyword) !== false) {
                $tags[] = $keyword;
            }
        }

        return $tags;
    }
}
