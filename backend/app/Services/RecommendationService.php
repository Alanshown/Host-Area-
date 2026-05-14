<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class RecommendationService
{
    /**
     * Track user interaction and update interests
     */
    public function trackInteraction(int $userId, string $categoryId, string $source, array $tags = []): void
    {
        // Track category interaction
        $this->upsertInterest($userId, $categoryId, $source, 1);

        // Track tags from content
        foreach ($tags as $tag) {
            $this->upsertInterest($userId, $tag, 'tag_' . $source, 1);
        }
    }

    /**
     * Upsert user interest record
     */
    protected function upsertInterest(int $userId, string $value, string $source, int $weight): void
    {
        if (empty($value)) return;

        DB::table('user_interests')->updateOrInsert(
            [
                'user_id' => $userId,
                'tag' => $value,
                'source' => $source,
            ],
            [
                'weight' => DB::raw('weight + ' . $weight),
                'updated_at' => now(),
            ]
        );
    }

    /**
     * Get user interest profile
     */
    public function getUserProfile(int $userId, int $limit = 10): array
    {
        $interests = DB::table('user_interests')
            ->select('tag', 'source', DB::raw('SUM(weight) as total_weight'))
            ->where('user_id', $userId)
            ->groupBy('tag', 'source')
            ->orderByDesc('total_weight')
            ->limit($limit)
            ->get();

        // Group by category
        $profile = [
            'categories' => [],
            'tags' => [],
            'sources' => [],
        ];

        foreach ($interests as $interest) {
            $item = [
                'name' => $interest->tag,
                'weight' => $interest->total_weight,
            ];

            if (str_starts_with($interest->source, 'tag_')) {
                $profile['tags'][] = $item;
            } else {
                $profile['categories'][] = $item;
            }

            if (!isset($profile['sources'][$interest->source])) {
                $profile['sources'][$interest->source] = 0;
            }
            $profile['sources'][$interest->source] += $interest->total_weight;
        }

        return $profile;
    }

    /**
     * Get recommended posts for a user
     */
    public function getRecommendedPosts(int $userId, int $limit = 10, int $excludePostId = null): array
    {
        // Get user's top interests
        $profile = $this->getUserProfile($userId);
        $topCategories = array_column(array_slice($profile['categories'], 0, 3), 'name');
        $topTags = array_column(array_slice($profile['tags'], 0, 5), 'name');

        // If no profile, return hot posts
        if (empty($topCategories) && empty($topTags)) {
            return $this->getHotPosts($limit, $excludePostId);
        }

        // Build recommendation query
        $query = DB::table('posts')
            ->join('users', 'posts.user_id', '=', 'users.id')
            ->leftJoin('categories', 'posts.category_id', '=', 'categories.id')
            ->leftJoin('likes', function ($join) {
                $join->on('posts.id', '=', 'likes.post_id')
                    ->where('likes.user_id', '!=', 0); // Just to get like count
            })
            ->leftJoin('comments', 'posts.id', '=', 'comments.post_id')
            ->select([
                'posts.*',
                'users.username',
                'users.avatar',
                'categories.name as category_name',
                DB::raw('(SELECT COUNT(*) FROM likes WHERE likes.post_id = posts.id) as likes_count'),
                DB::raw('(SELECT COUNT(*) FROM comments WHERE comments.post_id = posts.id) as comments_count'),
            ])
            ->where('posts.review_status', '!=', 'rejected')
            ->where('posts.user_id', '!=', $userId) // Exclude own posts
            ->orderByDesc('posts.created_at');

        if ($excludePostId) {
            $query->where('posts.id', '!=', $excludePostId);
        }

        // Score based on user interests
        if (!empty($topCategories)) {
            $query->addSelect(
                DB::raw('CASE WHEN posts.category_id IN ("' . implode('","', $topCategories) . '") THEN 2 ELSE 0 END as interest_score')
            );
        } else {
            $query->addSelect(DB::raw('0 as interest_score'));
        }

        $posts = $query
            ->orderByDesc('interest_score')
            ->orderByDesc('likes_count')
            ->limit($limit)
            ->get();

        return $this->formatPosts($posts);
    }

    /**
     * Get hot posts (fallback recommendations)
     */
    protected function getHotPosts(int $limit, $excludePostId = null): array
    {
        $query = DB::table('posts')
            ->join('users', 'posts.user_id', '=', 'users.id')
            ->leftJoin('categories', 'posts.category_id', '=', 'categories.id')
            ->select([
                'posts.*',
                'users.username',
                'users.avatar',
                'categories.name as category_name',
                DB::raw('(SELECT COUNT(*) FROM likes WHERE likes.post_id = posts.id) as likes_count'),
                DB::raw('(SELECT COUNT(*) FROM comments WHERE comments.post_id = posts.id) as comments_count'),
            ])
            ->where('posts.review_status', '!=', 'rejected')
            ->where('posts.created_at', '>=', now()->subDays(7)); // Recent posts

        if ($excludePostId) {
            $query->where('posts.id', '!=', $excludePostId);
        }

        $posts = $query
            ->orderByDesc('likes_count')
            ->orderByDesc('comments_count')
            ->limit($limit)
            ->get();

        return $this->formatPosts($posts);
    }

    /**
     * Get recommended users (similar users or popular users)
     */
    public function getRecommendedUsers(int $userId, int $limit = 10): array
    {
        // Get user's top interests
        $profile = $this->getUserProfile($userId);
        $topCategories = array_column(array_slice($profile['categories'], 0, 3), 'name');

        // Find users with similar interests
        $similarUserIds = [];
        if (!empty($topCategories)) {
            $similarUserIds = DB::table('user_interests')
                ->select('user_id', DB::raw('SUM(weight) as total_weight'))
                ->whereIn('tag', $topCategories)
                ->where('user_id', '!=', $userId)
                ->groupBy('user_id')
                ->orderByDesc('total_weight')
                ->limit($limit)
                ->pluck('user_id')
                ->toArray();
        }

        // Get active users with most followers/interactions
        $popularUsers = DB::table('users')
            ->leftJoin('user_follows', 'users.id', '=', 'user_follows.followed_user_id')
            ->leftJoin('posts', 'users.id', '=', 'posts.user_id')
            ->select([
                'users.id',
                'users.username',
                'users.avatar',
                'users.bio',
                DB::raw('COUNT(DISTINCT user_follows.follower_id) as followers_count'),
                DB::raw('COUNT(DISTINCT posts.id) as posts_count'),
            ])
            ->where('users.id', '!=', $userId)
            ->whereNull('users.banned_until')
            ->groupBy('users.id', 'users.username', 'users.avatar', 'users.bio')
            ->orderByDesc('followers_count')
            ->limit($limit)
            ->get();

        // Combine similar and popular users
        $userMap = [];
        foreach ($popularUsers as $user) {
            $userMap[$user->id] = $user;
        }

        // Prioritize similar users
        $results = [];
        foreach ($similarUserIds as $uid) {
            if (isset($userMap[$uid])) {
                $results[] = $userMap[$uid];
                unset($userMap[$uid]);
            }
        }

        // Add remaining popular users
        foreach ($userMap as $user) {
            if (count($results) >= $limit) break;
            $results[] = $user;
        }

        // Get following status for current user
        $followingIds = DB::table('user_follows')
            ->where('follower_id', $userId)
            ->whereIn('followed_user_id', array_column($results, 'id'))
            ->pluck('followed_user_id')
            ->toArray();
        $followingSet = array_flip($followingIds);

        return array_map(function ($user) use ($followingSet) {
            return [
                'id' => $user->id,
                'username' => $user->username,
                'avatar' => $user->avatar,
                'bio' => $user->bio ?? '',
                'followers_count' => $user->followers_count ?? 0,
                'posts_count' => $user->posts_count ?? 0,
                'is_following' => isset($followingSet[$user->id]),
            ];
        }, $results);
    }

    /**
     * Get trending categories/tags for a user
     */
    public function getTrendingForUser(int $userId, int $limit = 5): array
    {
        // Get user's top categories
        $profile = $this->getUserProfile($userId);
        $userCategories = array_column($profile['categories'], 'name');

        // Get trending categories based on recent likes/comments
        $trending = DB::table('user_interests')
            ->select('tag', DB::raw('SUM(weight) as score'))
            ->whereIn('source', ['like', 'favorite', 'post'])
            ->where('updated_at', '>=', now()->subDays(7))
            ->groupBy('tag')
            ->orderByDesc('score')
            ->limit($limit * 2)
            ->get();

        // Filter out user's existing interests and format
        $trendingList = [];
        foreach ($trending as $item) {
            if (!in_array($item->tag, $userCategories)) {
                $trendingList[] = [
                    'name' => $item->tag,
                    'score' => $item->score,
                    'is_new' => !in_array($item->tag, array_column($profile['tags'], 'name')),
                ];
            }
            if (count($trendingList) >= $limit) break;
        }

        return $trendingList;
    }

    /**
     * Format posts for API response
     */
    protected function formatPosts($posts): array
    {
        return $posts->map(function ($post) {
            return [
                'id' => $post->id,
                'title' => $post->title,
                'content' => mb_substr($post->content, 0, 200),
                'cover_image' => $post->cover_image,
                'category_id' => $post->category_id,
                'category_name' => $post->category_name ?? '',
                'user' => [
                    'id' => $post->user_id,
                    'username' => $post->username,
                    'avatar' => $post->avatar,
                ],
                'likes' => $post->likes_count ?? 0,
                'comments_count' => $post->comments_count ?? 0,
                'views' => $post->views ?? 0,
                'created_at' => $post->created_at,
            ];
        })->toArray();
    }

    /**
     * Refresh user profile weights based on recency
     */
    public function decayOldInterests(int $days = 30): int
    {
        $cutoff = now()->subDays($days);

        return DB::table('user_interests')
            ->where('updated_at', '<', $cutoff)
            ->update([
                'weight' => DB::raw('FLOOR(weight * 0.5)'),
                'updated_at' => now(),
            ]);
    }
}
