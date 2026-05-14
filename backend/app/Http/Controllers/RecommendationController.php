<?php

namespace App\Http\Controllers;

use App\Services\RecommendationService;
use Illuminate\Http\Request;

class RecommendationController extends Controller
{
    protected $recommendationService;

    public function __construct(RecommendationService $recommendationService)
    {
        $this->recommendationService = $recommendationService;
    }

    /**
     * Get personalized recommendations for the authenticated user
     */
    public function index(Request $request)
    {
        $request->validate([
            'type' => 'nullable|in:posts,users,trending',
            'limit' => 'nullable|integer|min:1|max:50',
            'exclude_post_id' => 'nullable|integer',
        ]);

        $userId = $request->user()->id ?? 0;
        $type = $request->input('type', 'posts');
        $limit = $request->input('limit', 10);
        $excludePostId = $request->input('exclude_post_id');

        if (!$userId) {
            return response()->json(['message' => '请先登录'], 401);
        }

        switch ($type) {
            case 'users':
                $data = $this->recommendationService->getRecommendedUsers($userId, $limit);
                break;

            case 'trending':
                $data = $this->recommendationService->getTrendingForUser($userId, $limit);
                break;

            case 'posts':
            default:
                $data = $this->recommendationService->getRecommendedPosts($userId, $limit, $excludePostId);
                break;
        }

        return response()->json(['data' => $data]);
    }

    /**
     * Get user interest profile
     */
    public function profile(Request $request)
    {
        $userId = $request->input('user_id', $request->user()->id ?? 0);

        if (!$userId) {
            return response()->json(['message' => '请先登录'], 401);
        }

        $profile = $this->recommendationService->getUserProfile($userId);

        return response()->json(['data' => $profile]);
    }

    /**
     * Track a user interaction
     */
    public function track(Request $request)
    {
        $request->validate([
            'category_id' => 'nullable|string|max:64',
            'source' => 'required|in:post,comment,like,favorite,view',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:64',
        ]);

        $userId = $request->user()->id ?? 0;

        if (!$userId) {
            return response()->json(['message' => '请先登录'], 401);
        }

        $this->recommendationService->trackInteraction(
            $userId,
            $request->input('category_id', ''),
            $request->input('source'),
            $request->input('tags', [])
        );

        return response()->json(['message' => '已记录']);
    }
}
