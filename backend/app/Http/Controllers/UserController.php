<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\User;
use App\Models\Post;
use App\Models\Comment;
use App\Models\Favorite;
use App\Models\ProfileVisit;
use App\Models\Report;
use App\Models\UserNotificationStatus;
use App\Models\UserFollow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function show(Request $request, $id)
    {
        $user = User::select(['id', 'username', 'avatar', 'profile_banners', 'bio', 'role', 'created_at'])
            ->withCount([
                'posts as posts_count' => fn ($query) => $query->where('moderation_status', 'approved'),
                'comments',
                'favorites',
                'followerUsers as followers_count',
                'followingUsers as following_count',
            ])
            ->findOrFail($id);

        $likesReceived = Post::where('user_id', $id)->where('moderation_status', 'approved')->sum('likes');
        $user->likes_received = $likesReceived;
        $user->honors = $this->buildHonors($user, $likesReceived);
        $viewer = $request->user();
        $user->is_following = $viewer
            ? UserFollow::where('follower_id', $viewer->id)->where('followed_user_id', $user->id)->exists()
            : false;
        $user->recent_visitors = ProfileVisit::where('profile_user_id', $id)
            ->with('visitor:id,username,avatar')
            ->latest('updated_at')
            ->take(8)
            ->get()
            ->map(function ($visit) {
                return $visit->visitor;
            })
            ->filter()
            ->values();

        return response()->json(['data' => $user]);
    }

    public function posts($id)
    {
        $posts = Post::where('user_id', $id)
            ->where('moderation_status', 'approved')
            ->with(['user:id,username,avatar', 'category:id,name'])
            ->withCount('comments')
            ->latest()
            ->paginate(10);

        return response()->json($posts);
    }

    public function comments($id)
    {
        $comments = Comment::where('user_id', $id)
            ->with([
                'user:id,username,avatar',
                'post:id,title,user_id,category_id',
                'post.user:id,username,avatar',
                'post.category:id,name',
            ])
            ->latest()
            ->paginate(10);

        return response()->json($comments);
    }

    public function favorites($id)
    {
        abort_unless(auth()->check() && auth()->id() === (int) $id, 403, '无权查看该收藏列表');

        $favorites = Favorite::where('user_id', $id)
            ->with(['post.user:id,username,avatar', 'post.category:id,name'])
            ->latest()
            ->paginate(10);

        return response()->json($favorites);
    }

    public function trackVisit(Request $request, $id)
    {
        $visitor = $request->user();

        if ($visitor->id !== (int) $id) {
            ProfileVisit::updateOrCreate(
                ['visitor_id' => $visitor->id, 'profile_user_id' => (int) $id],
                ['updated_at' => now()]
            );
        }

        return response()->json(['tracked' => true]);
    }

    public function reports(Request $request)
    {
        $reports = Report::where('user_id', $request->user()->id)
            ->with([
                'post:id,title,user_id,moderation_status',
                'post.user:id,username',
                'reviewer:id,username',
            ])
            ->latest()
            ->paginate(12);

        return response()->json($reports);
    }

    public function notifications(Request $request)
    {
        $user = $request->user();

        $commentNotifications = Comment::query()
            ->whereHas('post', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->where('user_id', '!=', $user->id)
            ->with([
                'user:id,username,avatar',
                'post:id,title,user_id',
            ])
            ->latest()
            ->take(10)
            ->get()
            ->map(function (Comment $comment) {
                return [
                    'id' => 'comment-' . $comment->id,
                    'type' => 'comment',
                    'title' => '收到了新的评论',
                    'headline' => ($comment->user?->username ?? '有用户') . ' 评论了你的帖子',
                    'detail' => $this->truncateNotificationText((string) $comment->content, 72),
                    'time' => optional($comment->created_at)->toIso8601String(),
                    'link' => $comment->post?->id ? '/post/' . $comment->post->id : null,
                    'actor' => $comment->user ? [
                        'id' => $comment->user->id,
                        'username' => $comment->user->username,
                        'avatar' => $comment->user->avatar,
                    ] : null,
                    'meta' => [
                        'post_title' => $comment->post?->title,
                    ],
                ];
            });

        $followNotifications = UserFollow::query()
            ->where('followed_user_id', $user->id)
            ->with('follower:id,username,avatar')
            ->latest()
            ->take(10)
            ->get()
            ->map(function (UserFollow $follow) {
                return [
                    'id' => 'follow-' . $follow->id,
                    'type' => 'follow',
                    'title' => '新增一位关注者',
                    'headline' => ($follow->follower?->username ?? '有用户') . ' 关注了你',
                    'detail' => '可以去对方主页看看，顺手回关也行。',
                    'time' => optional($follow->created_at)->toIso8601String(),
                    'link' => $follow->follower?->id ? '/user/' . $follow->follower->id : null,
                    'actor' => $follow->follower ? [
                        'id' => $follow->follower->id,
                        'username' => $follow->follower->username,
                        'avatar' => $follow->follower->avatar,
                    ] : null,
                    'meta' => [],
                ];
            });

        $reportNotifications = Report::query()
            ->where('user_id', $user->id)
            ->whereIn('status', ['in_review', 'resolved', 'rejected'])
            ->with([
                'post:id,title',
                'reviewer:id,username',
            ])
            ->latest('updated_at')
            ->take(10)
            ->get()
            ->map(function (Report $report) {
                $statusLabels = [
                    'in_review' => '正在处理',
                    'resolved' => '已处理完成',
                    'rejected' => '已驳回',
                ];

                return [
                    'id' => 'report-' . $report->id,
                    'type' => 'report',
                    'title' => '举报处理有了新进展',
                    'headline' => '你提交的举报当前状态：' . ($statusLabels[$report->status] ?? $report->status),
                    'detail' => $report->admin_note ?: ('关联帖子：' . ($report->post?->title ?? '帖子已删除')),
                    'time' => optional($report->updated_at)->toIso8601String(),
                    'link' => '/user/reports',
                    'actor' => $report->reviewer ? [
                        'id' => $report->reviewer->id,
                        'username' => $report->reviewer->username,
                        'avatar' => $report->reviewer->avatar ?? null,
                    ] : null,
                    'meta' => [
                        'status' => $report->status,
                        'post_title' => $report->post?->title,
                    ],
                ];
            });

        $announcementNotifications = Announcement::query()
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($query) {
                $query->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            })
            ->with('publisher:id,username')
            ->latest('updated_at')
            ->take(6)
            ->get()
            ->map(function (Announcement $announcement) {
                return [
                    'id' => 'announcement-' . $announcement->id,
                    'type' => 'system',
                    'title' => '系统公告',
                    'headline' => $announcement->title,
                    'detail' => $this->truncateNotificationText((string) $announcement->body, 90),
                    'time' => optional($announcement->updated_at)->toIso8601String(),
                    'link' => $announcement->link_url ?: null,
                    'actor' => $announcement->publisher ? [
                        'id' => $announcement->publisher->id,
                        'username' => $announcement->publisher->username,
                        'avatar' => $announcement->publisher->avatar ?? null,
                    ] : null,
                    'meta' => [
                        'link_label' => $announcement->link_label,
                        'external' => (bool) $announcement->link_url,
                    ],
                ];
            });

        $items = $commentNotifications
            ->concat($followNotifications)
            ->concat($reportNotifications)
            ->concat($announcementNotifications)
            ->sortByDesc('time')
            ->values();

        $readIds = UserNotificationStatus::query()
            ->where('user_id', $user->id)
            ->whereIn('notification_key', $items->pluck('id')->all())
            ->whereNotNull('read_at')
            ->pluck('notification_key')
            ->flip();

        $items = $items->map(function (array $item) use ($readIds) {
            $item['is_read'] = $readIds->has($item['id']);

            return $item;
        })->values();

        $unreadCount = $items->where('is_read', false)->count();

        return response()->json([
            'data' => $items->all(),
            'summary' => [
                'total' => $items->count(),
                'unread' => $unreadCount,
                'comments' => $commentNotifications->count(),
                'follows' => $followNotifications->count(),
                'reports' => $reportNotifications->count(),
                'system' => $announcementNotifications->count(),
            ],
        ]);
    }

    public function updateNotificationStatus(Request $request)
    {
        $payload = $request->validate([
            'ids' => ['nullable', 'array'],
            'ids.*' => ['string', 'max:191'],
            'read' => ['nullable', 'boolean'],
            'mark_all' => ['nullable', 'boolean'],
        ]);

        $user = $request->user();
        $read = $payload['read'] ?? true;
        $ids = collect($payload['ids'] ?? [])->filter()->unique()->values();

        if (($payload['mark_all'] ?? false) === true) {
            $items = collect($this->notifications($request)->getData(true)['data'] ?? []);
            $ids = $items->pluck('id')->filter()->unique()->values();
        }

        if ($ids->isEmpty()) {
            return response()->json([
                'message' => '未提供可更新的通知',
                'updated' => 0,
            ], 422);
        }

        if ($read) {
            $timestamp = now();
            $rows = $ids->map(function ($id) use ($user, $timestamp) {
                return [
                    'user_id' => $user->id,
                    'notification_key' => $id,
                    'read_at' => $timestamp,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
            })->all();

            UserNotificationStatus::query()->upsert(
                $rows,
                ['user_id', 'notification_key'],
                ['read_at', 'updated_at']
            );
        } else {
            UserNotificationStatus::query()
                ->where('user_id', $user->id)
                ->whereIn('notification_key', $ids->all())
                ->delete();
        }

        return response()->json([
            'message' => $read ? '通知已标记为已读' : '通知已标记为未读',
            'updated' => $ids->count(),
        ]);
    }

    public function followStatus(Request $request, $id)
    {
        $target = User::select('id')->findOrFail($id);
        $viewer = $request->user();

        return response()->json([
            'data' => [
                'user_id' => $target->id,
                'is_following' => UserFollow::where('follower_id', $viewer->id)
                    ->where('followed_user_id', $target->id)
                    ->exists(),
                'followers_count' => UserFollow::where('followed_user_id', $target->id)->count(),
                'following_count' => UserFollow::where('follower_id', $target->id)->count(),
            ],
        ]);
    }

    public function toggleFollow(Request $request, $id)
    {
        $viewer = $request->user();
        $target = User::findOrFail($id);

        if ($viewer->id === $target->id) {
            return response()->json(['message' => '不能关注自己'], 422);
        }

        $existing = UserFollow::where('follower_id', $viewer->id)
            ->where('followed_user_id', $target->id)
            ->first();

        if ($existing) {
            $existing->delete();
            $following = false;
            $message = '已取消关注';
        } else {
            UserFollow::create([
                'follower_id' => $viewer->id,
                'followed_user_id' => $target->id,
            ]);
            $following = true;
            $message = '关注成功';
        }

        return response()->json([
            'message' => $message,
            'data' => [
                'is_following' => $following,
                'followers_count' => UserFollow::where('followed_user_id', $target->id)->count(),
                'following_count' => UserFollow::where('follower_id', $target->id)->count(),
            ],
        ]);
    }

    public function followingFeed(Request $request)
    {
        $followingIds = UserFollow::where('follower_id', $request->user()->id)
            ->pluck('followed_user_id');

        if ($followingIds->isEmpty()) {
            return response()->json([
                'data' => [],
            ]);
        }

        $posts = Post::with(['user:id,username,avatar', 'category:id,name'])
            ->withCount('comments')
            ->whereIn('user_id', $followingIds)
            ->where('moderation_status', 'approved')
            ->latest()
            ->take(5)
            ->get();

        return response()->json([
            'data' => $posts,
        ]);
    }

    private function buildHonors(User $user, $likesReceived)
    {
        $honors = [];

        if ($user->posts_count >= 10) {
            $honors[] = ['label' => '高产创作者', 'tone' => 'amber'];
        }

        if ($likesReceived >= 50) {
            $honors[] = ['label' => '人气作者', 'tone' => 'rose'];
        }

        if ($user->comments_count >= 20) {
            $honors[] = ['label' => '讨论先锋', 'tone' => 'indigo'];
        }

        if ($user->favorites_count >= 10) {
            $honors[] = ['label' => '收藏达人', 'tone' => 'emerald'];
        }

        if (empty($honors)) {
            $honors[] = ['label' => '社区新星', 'tone' => 'sky'];
        }

        return $honors;
    }

    private function truncateNotificationText(string $text, int $limit): string
    {
        $normalized = trim(preg_replace('/\s+/u', ' ', $text) ?? '');

        if ($normalized === '') {
            return '';
        }

        $characters = preg_split('//u', $normalized, -1, PREG_SPLIT_NO_EMPTY);

        if ($characters === false || count($characters) <= $limit) {
            return $normalized;
        }

        return implode('', array_slice($characters, 0, $limit)) . '...';
    }
}
