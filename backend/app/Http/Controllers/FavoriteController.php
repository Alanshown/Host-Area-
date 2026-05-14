<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use App\Models\Post;
use App\Services\RecommendationService;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    protected $recommendationService;

    public function __construct(RecommendationService $recommendationService)
    {
        $this->recommendationService = $recommendationService;
    }

    public function toggle(Request $request, $postId)
    {
        $userId = $request->user()->id;
        $post   = Post::findOrFail($postId);

        $existing = Favorite::where('user_id', $userId)->where('post_id', $postId)->first();

        if ($existing) {
            $existing->delete();
            return response()->json(['favorited' => false]);
        }

        Favorite::create(['user_id' => $userId, 'post_id' => $postId]);

        // Track interaction for recommendations
        $this->recommendationService->trackInteraction(
            $userId,
            (string) $post->category_id,
            'favorite',
            $this->extractTags($post->title . ' ' . $post->content)
        );

        return response()->json(['favorited' => true]);
    }

    public function userFavorites(Request $request)
    {
        $favorites = Favorite::where('user_id', $request->user()->id)
            ->with(['post.user:id,username', 'post.category:id,name'])
            ->latest()
            ->paginate(10);

        return response()->json($favorites);
    }

    public function check(Request $request, $postId)
    {
        $favorited = Favorite::where('user_id', $request->user()->id)
            ->where('post_id', $postId)
            ->exists();

        return response()->json(['favorited' => $favorited]);
    }

    public function batchCheck(Request $request)
    {
        $ids = $request->query('ids', '');
        $postIds = array_filter(explode(',', $ids), fn($v) => is_numeric($v));
        if (empty($postIds)) return response()->json(['data' => []]);

        $favorited = Favorite::where('user_id', $request->user()->id)
            ->whereIn('post_id', $postIds)
            ->pluck('post_id')
            ->map(fn($v) => (int)$v)
            ->all();

        return response()->json(['data' => $favorited]);
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
