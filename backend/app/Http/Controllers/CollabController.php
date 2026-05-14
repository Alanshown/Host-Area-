<?php

namespace App\Http\Controllers;

use App\Services\CollabService;
use Illuminate\Http\Request;

class CollabController extends Controller
{
    protected $collabService;

    public function __construct(CollabService $collabService)
    {
        $this->collabService = $collabService;
    }

    /**
     * Get all active drafts for a channel
     */
    public function index(Request $request)
    {
        $request->validate([
            'channel' => 'nullable|string|max:64',
        ]);

        $channel = $request->input('channel', 'public-lobby');
        $drafts = $this->collabService->getChannelDrafts($channel);

        return response()->json(['data' => $drafts]);
    }

    /**
     * Get a specific draft
     */
    public function show(Request $request, $id)
    {
        $draft = $this->collabService->getDraft($id);

        if (empty($draft)) {
            return response()->json(['message' => '草稿不存在'], 404);
        }

        return response()->json(['data' => $draft]);
    }

    /**
     * Create a new collaboration draft
     */
    public function store(Request $request)
    {
        $request->validate([
            'channel' => 'nullable|string|max:64',
            'post_id' => 'nullable|integer|exists:posts,id',
        ]);

        $draft = $this->collabService->createDraft(
            $request->user()->id,
            $request->input('channel', 'public-lobby'),
            $request->input('post_id')
        );

        return response()->json(['data' => $draft], 201);
    }

    /**
     * Join an existing collaboration
     */
    public function join(Request $request, $id)
    {
        $result = $this->collabService->joinDraft($id, $request->user()->id);

        if (isset($result['error'])) {
            return response()->json(['message' => $result['error']], 400);
        }

        return response()->json(['data' => $result]);
    }

    /**
     * Update draft content
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'content' => 'required|string|max:100000',
            'cursor' => 'nullable|array',
            'cursor.line' => 'integer|min:0',
            'cursor.column' => 'integer|min:0',
        ]);

        $result = $this->collabService->updateContent(
            $id,
            $request->user()->id,
            $request->input('content'),
            $request->input('cursor')
        );

        if (isset($result['error'])) {
            return response()->json(['message' => $result['error']], 400);
        }

        return response()->json(['data' => $result]);
    }

    /**
     * Update cursor position only
     */
    public function updateCursor(Request $request, $id)
    {
        $request->validate([
            'position' => 'required|array',
            'position.line' => 'integer|min:0',
            'position.column' => 'integer|min:0',
        ]);

        $result = $this->collabService->updateCursor(
            $id,
            $request->user()->id,
            $request->input('position')
        );

        if (isset($result['error'])) {
            return response()->json(['message' => $result['error']], 400);
        }

        // Broadcast to other participants via SSE
        // This would be handled by a separate SSE endpoint
        return response()->json(['data' => $result]);
    }

    /**
     * Leave a collaboration
     */
    public function leave(Request $request, $id)
    {
        $this->collabService->leaveDraft($id, $request->user()->id);

        return response()->json(['message' => '已离开协作']);
    }

    /**
     * Delete a draft
     */
    public function destroy(Request $request, $id)
    {
        $result = $this->collabService->deleteDraft($id, $request->user()->id);

        if (!$result) {
            return response()->json(['message' => '无法删除该草稿'], 403);
        }

        return response()->json(['message' => '已删除']);
    }

    /**
     * Stream draft updates (SSE endpoint)
     */
    public function stream(Request $request, $id)
    {
        // Set up SSE headers
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');

        $userId = $request->user()->id;
        $lastUpdate = 0;

        // Join the collaboration
        $this->collabService->joinDraft($id, $userId);

        // Keep connection alive and send updates
        while (true) {
            // Check if client disconnected
            if (connection_aborted()) {
                break;
            }

            // Get current draft state
            $draft = $this->collabService->getDraft($id);

            if (empty($draft)) {
                echo "event: error\n";
                echo "data: " . json_encode(['message' => 'Draft not found']) . "\n\n";
                break;
            }

            // Check for updates
            $updatedAt = strtotime($draft['updated_at']);
            if ($updatedAt > $lastUpdate) {
                echo "event: update\n";
                echo "data: " . json_encode([
                    'content' => $draft['content'],
                    'cursors' => $draft['cursors'],
                    'participants' => $draft['participants'],
                    'updated_at' => $draft['updated_at'],
                ]) . "\n\n";
                $lastUpdate = $updatedAt;
            }

            // Send heartbeat
            echo "event: heartbeat\n";
            echo "data: " . json_encode(['time' => time()]) . "\n\n";

            // Flush output
            if (ob_get_level()) {
                ob_flush();
            }
            flush();

            // Wait before next check (500ms)
            usleep(500000);
        }

        // Clean up on disconnect
        $this->collabService->leaveDraft($id, $userId);
        exit;
    }
}
