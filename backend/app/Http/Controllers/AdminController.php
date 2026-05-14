<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Category;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    private function requireAdmin(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            abort(403, '权限不足');
        }
    }

    public function stats(Request $request)
    {
        $this->requireAdmin($request);

        $periodEnd = now();
        $currentPeriodStart = (clone $periodEnd)->subDays(6)->startOfDay();
        $previousPeriodStart = (clone $currentPeriodStart)->subDays(7);
        $previousPeriodEnd = (clone $currentPeriodStart)->subSecond();

        $labels = $this->lastDaysLabels(14);
        $startDate = now()->subDays(count($labels) - 1)->startOfDay();

        $activeUserIds = Comment::query()
            ->where('created_at', '>=', now()->subDays(7))
            ->pluck('user_id')
            ->merge(
                Post::query()->where('created_at', '>=', now()->subDays(7))->pluck('user_id')
            )
            ->unique()
            ->filter();

        $categoryHeat = Category::query()
            ->leftJoin('posts', 'posts.category_id', '=', 'categories.id')
            ->select(
                'categories.id',
                'categories.name',
                DB::raw('COUNT(posts.id) as posts_count'),
                DB::raw('COALESCE(SUM(posts.likes), 0) as likes_total'),
                DB::raw('COALESCE(SUM(posts.views), 0) as views_total')
            )
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('posts_count')
            ->limit(6)
            ->get();

        $userGrowth = $this->buildDailySeries('users', $labels, $startDate);
        $postSeries = $this->buildDailySeries('posts', $labels, $startDate);
        $commentSeries = $this->buildDailySeries('comments', $labels, $startDate);
        $reportSeries = $this->buildDailySeries('reports', $labels, $startDate);

        $roleDistribution = User::query()
            ->select('role', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('role')
            ->pluck('aggregate', 'role');

        $postModeration = Post::query()
            ->select('moderation_status', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('moderation_status')
            ->pluck('aggregate', 'moderation_status');

        $reportStatuses = Report::query()
            ->select('status', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $currentPostsCreated = Post::query()
            ->whereBetween('created_at', [$currentPeriodStart, $periodEnd])
            ->count();
        $previousPostsCreated = Post::query()
            ->whereBetween('created_at', [$previousPeriodStart, $previousPeriodEnd])
            ->count();

        $currentUsersCreated = User::query()
            ->whereBetween('created_at', [$currentPeriodStart, $periodEnd])
            ->count();
        $previousUsersCreated = User::query()
            ->whereBetween('created_at', [$previousPeriodStart, $previousPeriodEnd])
            ->count();

        $currentReportsCreated = Report::query()
            ->whereBetween('created_at', [$currentPeriodStart, $periodEnd])
            ->count();
        $previousReportsCreated = Report::query()
            ->whereBetween('created_at', [$previousPeriodStart, $previousPeriodEnd])
            ->count();

        $currentActiveUsers = $this->activeUsersBetween($currentPeriodStart, $periodEnd)->count();
        $previousActiveUsers = $this->activeUsersBetween($previousPeriodStart, $previousPeriodEnd)->count();

        $topContributors = User::query()
            ->withCount(['posts', 'comments', 'reports'])
            ->select(['id', 'username', 'avatar', 'role'])
            ->orderByDesc('posts_count')
            ->orderByDesc('comments_count')
            ->limit(6)
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'username' => $user->username,
                'avatar' => $user->avatar,
                'role' => $user->role,
                'posts_count' => $user->posts_count,
                'comments_count' => $user->comments_count,
                'reports_count' => $user->reports_count,
                'engagement_score' => ((int) $user->posts_count * 5) + ((int) $user->comments_count * 2) + (int) $user->reports_count,
            ])
            ->values();

        $recentOps = collect([
            ...Report::query()
                ->with(['user:id,username', 'post:id,title'])
                ->latest('updated_at')
                ->limit(4)
                ->get()
                ->map(fn (Report $report) => [
                    'type' => 'report',
                    'title' => '举报流转',
                    'headline' => sprintf('举报 #%d 当前状态：%s', $report->id, $report->status),
                    'detail' => sprintf('%s 提交，关联帖子：%s', $report->user?->username ?? '未知用户', $report->post?->title ?? '帖子已删除'),
                    'time' => optional($report->updated_at)->toIso8601String(),
                ])
                ->all(),
            ...Announcement::query()
                ->with('publisher:id,username')
                ->latest('updated_at')
                ->limit(3)
                ->get()
                ->map(fn (Announcement $announcement) => [
                    'type' => 'announcement',
                    'title' => '公告更新',
                    'headline' => $announcement->title,
                    'detail' => sprintf('%s · %s', $announcement->publisher?->username ?? '未知管理员', $announcement->is_active ? '已启用' : '未启用'),
                    'time' => optional($announcement->updated_at)->toIso8601String(),
                ])
                ->all(),
        ])
            ->sortByDesc('time')
            ->take(6)
            ->values();

        return response()->json([
            'posts_count' => Post::count(),
            'users_count' => User::count(),
            'comments_count' => Comment::count(),
            'pending_posts_count' => Post::where('moderation_status', 'pending')->count(),
            'reports_count' => Report::whereIn('status', ['pending', 'in_review'])->count(),
            'announcements_count' => Announcement::count(),
            'active_users_count' => $activeUserIds->count(),
            'metric_trends' => [
                'posts' => $this->percentageChange($currentPostsCreated, $previousPostsCreated),
                'users' => $this->percentageChange($currentUsersCreated, $previousUsersCreated),
                'active_users' => $this->percentageChange($currentActiveUsers, $previousActiveUsers),
                'reports' => $this->percentageChange($currentReportsCreated, $previousReportsCreated),
            ],
            'category_heat' => $categoryHeat,
            'chart_labels' => $labels,
            'user_growth_series' => $userGrowth,
            'content_activity_series' => [
                'posts' => $postSeries,
                'comments' => $commentSeries,
                'reports' => $reportSeries,
            ],
            'role_distribution' => [
                ['label' => '普通用户', 'key' => 'user', 'value' => (int) ($roleDistribution['user'] ?? 0)],
                ['label' => '管理员', 'key' => 'admin', 'value' => (int) ($roleDistribution['admin'] ?? 0)],
                ['label' => '封禁中', 'key' => 'banned', 'value' => User::query()->where('banned_until', '>', now())->count()],
            ],
            'moderation_breakdown' => [
                'posts' => [
                    ['label' => '待审核', 'key' => 'pending', 'value' => (int) ($postModeration['pending'] ?? 0)],
                    ['label' => '已通过', 'key' => 'approved', 'value' => (int) ($postModeration['approved'] ?? 0)],
                    ['label' => '已驳回', 'key' => 'rejected', 'value' => (int) ($postModeration['rejected'] ?? 0)],
                ],
                'reports' => [
                    ['label' => '待处理', 'key' => 'pending', 'value' => (int) ($reportStatuses['pending'] ?? 0)],
                    ['label' => '审核中', 'key' => 'in_review', 'value' => (int) ($reportStatuses['in_review'] ?? 0)],
                    ['label' => '已解决', 'key' => 'resolved', 'value' => (int) ($reportStatuses['resolved'] ?? 0)],
                    ['label' => '已驳回', 'key' => 'rejected', 'value' => (int) ($reportStatuses['rejected'] ?? 0)],
                ],
            ],
            'top_contributors' => $topContributors,
            'database_overview' => $this->databaseOverview(),
            'recent_ops' => $recentOps,
        ]);
    }

    public function users(Request $request)
    {
        $this->requireAdmin($request);
        $users = User::select(['id', 'username', 'email', 'role', 'bio', 'banned_until', 'ban_reason', 'created_at'])
            ->withCount(['posts', 'comments', 'reports'])
            ->latest()
            ->paginate(20);

        $users->getCollection()->transform(function (User $user) {
            return [
                ...$user->toArray(),
                'is_banned' => $user->isBanned() || $user->role === 'banned',
            ];
        });

        return response()->json($users);
    }

    public function banUser(Request $request, $id)
    {
        $this->requireAdmin($request);
        $user = User::findOrFail($id);

        if ($user->role === 'admin' && (int) $user->id !== (int) $request->user()->id) {
            abort(422, '不能在此处直接封禁其他管理员');
        }

        if ($user->isBanned() || $user->role === 'banned') {
            $user->banned_until = null;
            $user->ban_reason = null;
            if ($user->role === 'banned') {
                $user->role = 'user';
            }
        } else {
            $user->banned_until = now()->addYears(10);
            $user->ban_reason = '管理员在平台管理后台执行长期封禁';
        }

        $user->save();

        if ($user->isBanned()) {
            $user->tokens()->delete();
        }

        return response()->json([
            'role' => $user->role,
            'is_banned' => $user->isBanned() || $user->role === 'banned',
            'banned_until' => optional($user->banned_until)->toIso8601String(),
        ]);
    }

    public function posts(Request $request)
    {
        $this->requireAdmin($request);
        $posts = Post::with(['user:id,username', 'category:id,name'])
            ->withCount('comments')
            ->orderByRaw("FIELD(moderation_status, 'pending', 'rejected', 'approved')")
            ->latest()
            ->paginate(20);
        return response()->json($posts);
    }

    public function moderatePost(Request $request, $id)
    {
        $this->requireAdmin($request);

        $validated = $request->validate([
            'status' => 'required|in:approved,rejected,pending',
            'moderation_note' => 'nullable|string|max:500',
        ]);

        $post = Post::findOrFail($id);
        $post->update([
            'moderation_status' => $validated['status'],
            'moderation_note' => $validated['moderation_note'] ?? null,
            'moderated_by' => $request->user()->id,
            'moderated_at' => now(),
        ]);

        return response()->json(['data' => $post->fresh(['user:id,username', 'category:id,name'])]);
    }

    public function deletePost(Request $request, $id)
    {
        $this->requireAdmin($request);
        Post::findOrFail($id)->delete();
        return response()->json(['message' => '已删除']);
    }

    public function comments(Request $request)
    {
        $this->requireAdmin($request);
        $comments = Comment::with(['user:id,username', 'post:id,title'])
            ->latest()
            ->paginate(20);
        return response()->json($comments);
    }

    public function deleteComment(Request $request, $id)
    {
        $this->requireAdmin($request);
        Comment::findOrFail($id)->delete();
        return response()->json(['message' => '已删除']);
    }

    public function reports(Request $request)
    {
        $this->requireAdmin($request);

        $reports = Report::with([
            'user:id,username,email',
            'post:id,title,user_id,moderation_status',
            'post.user:id,username',
            'reviewer:id,username',
        ])
            ->orderByRaw("FIELD(status, 'pending', 'in_review', 'resolved', 'rejected')")
            ->latest()
            ->paginate(20);

        return response()->json($reports);
    }

    public function reportDetail(Request $request, $id)
    {
        $this->requireAdmin($request);

        $report = Report::with([
            'user:id,username,email',
            'post:id,title,content,user_id,category_id,moderation_status,moderation_note,created_at',
            'post.user:id,username',
            'post.category:id,name',
            'reviewer:id,username',
        ])->findOrFail($id);

        return response()->json(['data' => $report]);
    }

    public function updateReport(Request $request, $id)
    {
        $this->requireAdmin($request);

        $validated = $request->validate([
            'status' => 'required|in:pending,in_review,resolved,rejected',
            'admin_note' => 'nullable|string|max:1000',
        ]);

        $report = Report::findOrFail($id);
        $report->update([
            'status' => $validated['status'],
            'admin_note' => $validated['admin_note'] ?? null,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        return response()->json(['data' => $report->fresh(['reviewer:id,username'])]);
    }

    public function announcements(Request $request)
    {
        $this->requireAdmin($request);

        $announcements = Announcement::with('publisher:id,username')
            ->latest()
            ->paginate(12);

        return response()->json($announcements);
    }

    public function storeAnnouncement(Request $request)
    {
        $this->requireAdmin($request);

        $validated = $request->validate([
            'title' => 'required|string|max:120',
            'body' => 'required|string|max:600',
            'link_url' => 'nullable|string|max:500',
            'link_label' => 'nullable|string|max:80',
            'is_active' => 'sometimes|boolean',
        ]);

        $announcement = Announcement::create([
            ...$validated,
            'is_active' => $validated['is_active'] ?? true,
            'published_by' => $request->user()->id,
        ]);

        return response()->json(['data' => $announcement->load('publisher:id,username')], 201);
    }

    public function updateAnnouncement(Request $request, $id)
    {
        $this->requireAdmin($request);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:120',
            'body' => 'sometimes|string|max:600',
            'link_url' => 'nullable|string|max:500',
            'link_label' => 'nullable|string|max:80',
            'is_active' => 'sometimes|boolean',
        ]);

        $announcement = Announcement::findOrFail($id);
        $announcement->update($validated);

        return response()->json(['data' => $announcement->fresh('publisher:id,username')]);
    }

    public function deleteAnnouncement(Request $request, $id)
    {
        $this->requireAdmin($request);
        Announcement::findOrFail($id)->delete();
        return response()->json(['message' => '已删除']);
    }

    public function databaseRecords(Request $request)
    {
        $this->requireAdmin($request);

        $validated = $request->validate([
            'entity' => 'required|in:users,posts,comments,announcements,reports',
            'keyword' => 'nullable|string|max:100',
            'page' => 'nullable|integer|min:1',
        ]);

        $entity = $validated['entity'];
        $keyword = trim((string) ($validated['keyword'] ?? ''));

        return response()->json([
            'data' => [
                'entity' => $entity,
                'label' => $this->entityLabel($entity),
                'fields' => $this->databaseFields($entity),
                'records' => $this->databasePaginator($entity, $keyword),
            ],
        ]);
    }

    public function updateDatabaseRecord(Request $request, string $entity, int $id)
    {
        $this->requireAdmin($request);
        abort_unless(in_array($entity, ['users', 'posts', 'comments', 'announcements', 'reports'], true), 404);

        $record = match ($entity) {
            'users' => $this->updateDatabaseUser($request, $id),
            'posts' => $this->updateDatabasePost($request, $id),
            'comments' => $this->updateDatabaseComment($request, $id),
            'announcements' => $this->updateDatabaseAnnouncement($request, $id),
            'reports' => $this->updateDatabaseReport($request, $id),
        };

        return response()->json([
            'data' => [
                'entity' => $entity,
                'record' => $record,
            ],
        ]);
    }

    private function lastDaysLabels(int $days): array
    {
        return collect(range($days - 1, 0))
            ->map(fn (int $offset) => now()->subDays($offset)->format('m/d'))
            ->values()
            ->all();
    }

    private function buildDailySeries(string $table, array $labels, $startDate): array
    {
        $rows = DB::table($table)
            ->selectRaw('DATE(created_at) as day, COUNT(*) as aggregate')
            ->where('created_at', '>=', $startDate)
            ->groupBy('day')
            ->pluck('aggregate', 'day');

        return collect($labels)
            ->values()
            ->map(function (string $label, int $index) use ($rows, $startDate) {
                $dayKey = (clone $startDate)->addDays($index)->toDateString();

                return (int) ($rows[$dayKey] ?? 0);
            })
            ->all();
    }

    private function activeUsersBetween($start, $end)
    {
        return Comment::query()
            ->whereBetween('created_at', [$start, $end])
            ->pluck('user_id')
            ->merge(
                Post::query()->whereBetween('created_at', [$start, $end])->pluck('user_id')
            )
            ->unique()
            ->filter();
    }

    private function percentageChange(int $current, int $previous): float
    {
        if ($previous === 0) {
            if ($current === 0) {
                return 0;
            }

            return 100;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    private function databaseOverview(): array
    {
        return [
            ['entity' => 'users', 'label' => '用户', 'count' => User::count()],
            ['entity' => 'posts', 'label' => '帖子', 'count' => Post::count()],
            ['entity' => 'comments', 'label' => '评论', 'count' => Comment::count()],
            ['entity' => 'announcements', 'label' => '公告', 'count' => Announcement::count()],
            ['entity' => 'reports', 'label' => '举报', 'count' => Report::count()],
        ];
    }

    private function entityLabel(string $entity): string
    {
        return [
            'users' => '用户',
            'posts' => '帖子',
            'comments' => '评论',
            'announcements' => '公告',
            'reports' => '举报',
        ][$entity] ?? $entity;
    }

    private function databaseFields(string $entity): array
    {
        return match ($entity) {
            'users' => [
                ['key' => 'username', 'label' => '用户名', 'type' => 'text'],
                ['key' => 'email', 'label' => '邮箱', 'type' => 'email'],
                ['key' => 'role', 'label' => '身份', 'type' => 'select', 'options' => [
                    ['label' => '普通用户', 'value' => 'user'],
                    ['label' => '管理员', 'value' => 'admin'],
                    ['label' => '封禁标记', 'value' => 'banned'],
                ]],
                ['key' => 'bio', 'label' => '简介', 'type' => 'textarea'],
                ['key' => 'ban_reason', 'label' => '封禁原因', 'type' => 'text'],
                ['key' => 'banned_until', 'label' => '封禁截止', 'type' => 'datetime-local'],
            ],
            'posts' => [
                ['key' => 'title', 'label' => '标题', 'type' => 'text'],
                ['key' => 'category_id', 'label' => '分类 ID', 'type' => 'number'],
                ['key' => 'moderation_status', 'label' => '审核状态', 'type' => 'select', 'options' => [
                    ['label' => '待审核', 'value' => 'pending'],
                    ['label' => '已通过', 'value' => 'approved'],
                    ['label' => '已驳回', 'value' => 'rejected'],
                ]],
                ['key' => 'moderation_note', 'label' => '审核备注', 'type' => 'textarea'],
            ],
            'comments' => [
                ['key' => 'content', 'label' => '评论内容', 'type' => 'textarea'],
            ],
            'announcements' => [
                ['key' => 'title', 'label' => '标题', 'type' => 'text'],
                ['key' => 'body', 'label' => '正文', 'type' => 'textarea'],
                ['key' => 'link_label', 'label' => '链接文案', 'type' => 'text'],
                ['key' => 'link_url', 'label' => '链接地址', 'type' => 'text'],
                ['key' => 'is_active', 'label' => '启用状态', 'type' => 'checkbox'],
            ],
            'reports' => [
                ['key' => 'status', 'label' => '处理状态', 'type' => 'select', 'options' => [
                    ['label' => '待处理', 'value' => 'pending'],
                    ['label' => '审核中', 'value' => 'in_review'],
                    ['label' => '已解决', 'value' => 'resolved'],
                    ['label' => '已驳回', 'value' => 'rejected'],
                ]],
                ['key' => 'admin_note', 'label' => '审核备注', 'type' => 'textarea'],
            ],
        };
    }

    private function databasePaginator(string $entity, string $keyword = ''): LengthAwarePaginator
    {
        return match ($entity) {
            'users' => $this->databaseUsersPaginator($keyword),
            'posts' => $this->databasePostsPaginator($keyword),
            'comments' => $this->databaseCommentsPaginator($keyword),
            'announcements' => $this->databaseAnnouncementsPaginator($keyword),
            'reports' => $this->databaseReportsPaginator($keyword),
        };
    }

    private function databaseUsersPaginator(string $keyword = ''): LengthAwarePaginator
    {
        $query = User::query()
            ->select(['id', 'username', 'email', 'role', 'bio', 'ban_reason', 'banned_until', 'created_at'])
            ->withCount(['posts', 'comments', 'reports'])
            ->latest();

        if ($keyword !== '') {
            $query->where(function ($builder) use ($keyword) {
                $builder->where('username', 'like', "%{$keyword}%")
                    ->orWhere('email', 'like', "%{$keyword}%");
            });
        }

        $paginator = $query->paginate(8);
        $paginator->getCollection()->transform(fn (User $user) => [
            ...$user->toArray(),
            'is_banned' => $user->isBanned() || $user->role === 'banned',
        ]);

        return $paginator;
    }

    private function databasePostsPaginator(string $keyword = ''): LengthAwarePaginator
    {
        $query = Post::query()
            ->with(['user:id,username', 'category:id,name'])
            ->select(['id', 'title', 'category_id', 'user_id', 'moderation_status', 'moderation_note', 'created_at'])
            ->latest();

        if ($keyword !== '') {
            $query->where(function ($builder) use ($keyword) {
                $builder->where('title', 'like', "%{$keyword}%")
                    ->orWhere('content', 'like', "%{$keyword}%");
            });
        }

        return $query->paginate(8);
    }

    private function databaseCommentsPaginator(string $keyword = ''): LengthAwarePaginator
    {
        $query = Comment::query()
            ->with(['user:id,username', 'post:id,title'])
            ->select(['id', 'content', 'user_id', 'post_id', 'created_at'])
            ->latest();

        if ($keyword !== '') {
            $query->where('content', 'like', "%{$keyword}%");
        }

        return $query->paginate(8);
    }

    private function databaseAnnouncementsPaginator(string $keyword = ''): LengthAwarePaginator
    {
        $query = Announcement::query()
            ->with('publisher:id,username')
            ->select(['id', 'title', 'body', 'link_label', 'link_url', 'is_active', 'published_by', 'created_at'])
            ->latest();

        if ($keyword !== '') {
            $query->where(function ($builder) use ($keyword) {
                $builder->where('title', 'like', "%{$keyword}%")
                    ->orWhere('body', 'like', "%{$keyword}%");
            });
        }

        return $query->paginate(8);
    }

    private function databaseReportsPaginator(string $keyword = ''): LengthAwarePaginator
    {
        $query = Report::query()
            ->with(['user:id,username', 'post:id,title'])
            ->select(['id', 'reason', 'status', 'admin_note', 'user_id', 'post_id', 'created_at'])
            ->latest();

        if ($keyword !== '') {
            $query->where('reason', 'like', "%{$keyword}%");
        }

        return $query->paginate(8);
    }

    private function updateDatabaseUser(Request $request, int $id): array
    {
        $validated = $request->validate([
            'username' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|max:255',
            'role' => 'sometimes|in:user,admin,banned',
            'bio' => 'nullable|string|max:1000',
            'ban_reason' => 'nullable|string|max:255',
            'banned_until' => 'nullable|date',
        ]);

        $user = User::findOrFail($id);
        $user->fill($validated);

        if (array_key_exists('banned_until', $validated)) {
            $user->banned_until = $validated['banned_until'] ? \Illuminate\Support\Carbon::parse($validated['banned_until']) : null;
        }

        if (($validated['role'] ?? null) !== 'banned' && ! $user->banned_until) {
            $user->ban_reason = $validated['ban_reason'] ?? null;
        }

        $user->save();

        if ($user->isBanned()) {
            $user->tokens()->delete();
        }

        return [
            ...$user->fresh()->toArray(),
            'is_banned' => $user->isBanned() || $user->role === 'banned',
        ];
    }

    private function updateDatabasePost(Request $request, int $id): array
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'category_id' => 'sometimes|nullable|integer|exists:categories,id',
            'moderation_status' => 'sometimes|in:pending,approved,rejected',
            'moderation_note' => 'nullable|string|max:500',
        ]);

        $post = Post::findOrFail($id);
        $post->fill($validated);

        if (array_key_exists('moderation_status', $validated)) {
            $post->moderated_by = $request->user()->id;
            $post->moderated_at = now();
        }

        $post->save();

        return $post->fresh(['user:id,username', 'category:id,name'])->toArray();
    }

    private function updateDatabaseComment(Request $request, int $id): array
    {
        $validated = $request->validate([
            'content' => 'required|string|max:4000',
        ]);

        $comment = Comment::findOrFail($id);
        $comment->update($validated);

        return $comment->fresh(['user:id,username', 'post:id,title'])->toArray();
    }

    private function updateDatabaseAnnouncement(Request $request, int $id): array
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:120',
            'body' => 'sometimes|string|max:600',
            'link_url' => 'nullable|string|max:500',
            'link_label' => 'nullable|string|max:80',
            'is_active' => 'sometimes|boolean',
        ]);

        $announcement = Announcement::findOrFail($id);
        $announcement->update($validated);

        return $announcement->fresh('publisher:id,username')->toArray();
    }

    private function updateDatabaseReport(Request $request, int $id): array
    {
        $validated = $request->validate([
            'status' => 'sometimes|in:pending,in_review,resolved,rejected',
            'admin_note' => 'nullable|string|max:1000',
        ]);

        $report = Report::findOrFail($id);
        $report->fill($validated);
        $report->reviewed_by = $request->user()->id;
        $report->reviewed_at = now();
        $report->save();

        return $report->fresh(['user:id,username', 'post:id,title', 'reviewer:id,username'])->toArray();
    }
}
