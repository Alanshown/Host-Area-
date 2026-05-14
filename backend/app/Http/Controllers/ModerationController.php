<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ContentModerationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ModerationController extends Controller
{
    protected $moderationService;

    public function __construct(ContentModerationService $moderationService)
    {
        $this->moderationService = $moderationService;
    }

    /**
     * Get moderation queue (for admin)
     */
    public function index(Request $request)
    {
        $request->validate([
            'status' => 'nullable|in:pending,approved,rejected',
            'type' => 'nullable|in:post,comment,chat_message',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:50',
        ]);

        $query = DB::table('moderation_queue')
            ->leftJoin('users', 'moderation_queue.user_id', '=', 'users.id')
            ->select([
                'moderation_queue.*',
                'users.username',
                'users.avatar',
            ])
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('type')) {
            $query->where('content_type', $request->type);
        }

        // Only show pending by default
        if (!$request->filled('status')) {
            $query->where('status', 'pending');
        }

        $perPage = $request->input('per_page', 20);
        $items = $query->paginate($perPage);

        return response()->json([
            'data' => $items->items(),
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
            ],
        ]);
    }

    /**
     * Get moderation statistics
     */
    public function stats()
    {
        $stats = $this->moderationService->getStats();

        // Add trend data (last 7 days)
        $trend = DB::table('moderation_queue')
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN status = "approved" THEN 1 ELSE 0 END) as approved'),
                DB::raw('SUM(CASE WHEN status = "rejected" THEN 1 ELSE 0 END) as rejected'),
            )
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();

        return response()->json([
            'data' => [
                'summary' => $stats,
                'trend' => $trend,
            ],
        ]);
    }

    /**
     * Approve a content item
     */
    public function approve(Request $request, $id)
    {
        $this->requireAdmin($request);

        $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);

        $item = DB::table('moderation_queue')->find($id);

        if (!$item) {
            return response()->json(['message' => '审核项不存在'], 404);
        }

        // Update moderation queue
        DB::table('moderation_queue')
            ->where('id', $id)
            ->update([
                'status' => 'approved',
                'moderator_id' => $request->user()->id,
                'moderated_at' => now(),
                'reason' => $request->input('notes', '审核通过'),
            ]);

        // If it was a post, update its review status
        if ($item->content_type === 'post' && $item->content_id) {
            DB::table('posts')
                ->where('id', $item->content_id)
                ->update([
                    'review_status' => 'approved',
                    'review_reason' => '人工审核通过',
                    'reviewed_at' => now(),
                ]);
        }

        return response()->json([
            'message' => '审核通过',
            'data' => ['id' => $id],
        ]);
    }

    /**
     * Reject a content item
     */
    public function reject(Request $request, $id)
    {
        $this->requireAdmin($request);

        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $item = DB::table('moderation_queue')->find($id);

        if (!$item) {
            return response()->json(['message' => '审核项不存在'], 404);
        }

        // Update moderation queue
        DB::table('moderation_queue')
            ->where('id', $id)
            ->update([
                'status' => 'rejected',
                'moderator_id' => $request->user()->id,
                'moderated_at' => now(),
                'reason' => $request->reason,
            ]);

        // If it was a post, delete or mark it
        if ($item->content_type === 'post' && $item->content_id) {
            DB::table('posts')
                ->where('id', $item->content_id)
                ->update([
                    'review_status' => 'rejected',
                    'review_reason' => $request->reason,
                    'reviewed_at' => now(),
                ]);
        }

        return response()->json([
            'message' => '已拒绝该内容',
            'data' => ['id' => $id],
        ]);
    }

    /**
     * Preview moderation result for a piece of content
     */
    public function preview(Request $request)
    {
        $request->validate([
            'content' => 'required|string|max:10000',
            'type' => 'nullable|in:post,comment,chat_message',
        ]);

        $result = $this->moderationService->analyzeContent(
            $request->input('content'),
            $request->input('type', 'post')
        );

        return response()->json(['data' => $result]);
    }

    /**
     * Batch process moderation queue
     */
    public function batchAction(Request $request)
    {
        $this->requireAdmin($request);

        $request->validate([
            'ids' => 'required|array|min:1|max:100',
            'ids.*' => 'integer',
            'action' => 'required|in:approve,reject',
            'reason' => 'required_if:action,reject|string|max:500',
        ]);

        $ids = $request->input('ids');
        $action = $request->input('action');

        $updateData = [
            'status' => $action === 'approve' ? 'approved' : 'rejected',
            'moderator_id' => $request->user()->id,
            'moderated_at' => now(),
        ];

        if ($action === 'reject') {
            $updateData['reason'] = $request->input('reason');
        }

        $affected = DB::table('moderation_queue')
            ->whereIn('id', $ids)
            ->update($updateData);

        return response()->json([
            'message' => "已处理 {$affected} 项",
            'data' => ['affected' => $affected],
        ]);
    }

    protected function requireAdmin(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            abort(403, '权限不足，需要管理员权限');
        }
    }
}
