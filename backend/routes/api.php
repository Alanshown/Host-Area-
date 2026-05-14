<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PostController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\LikeController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ModerationController;
use App\Http\Controllers\RecommendationController;
use App\Http\Controllers\CollabController;
use App\Http\Controllers\SensitiveWordController;
use App\Http\Controllers\TTSController;
use App\Http\Controllers\ModelConfigController;
use App\Models\Post;
use App\Models\User;

// ── Auth ───────────────────────────────────────────────────────────────────
Route::post('/auth/login',    [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me',      [AuthController::class, 'me']);
    Route::put('/auth/profile', [AuthController::class, 'updateProfile']);
    Route::post('/auth/profile', [AuthController::class, 'updateProfile']);
});

// ── Public read ────────────────────────────────────────────────────────────
Route::get('/posts',               [PostController::class, 'index']);
Route::get('/posts/hot',           [PostController::class, 'hot']);
Route::get('/posts/{id}',          [PostController::class, 'show']);
Route::get('/posts/{id}/comments', [CommentController::class, 'index']);
Route::get('/categories',          [CategoryController::class, 'index']);
Route::get('/users/{id}',          [UserController::class, 'show']);
Route::get('/users/{id}/posts',    [UserController::class, 'posts']);
Route::get('/users/{id}/comments', [UserController::class, 'comments']);
Route::get('/announcements/current', [AnnouncementController::class, 'current']);
Route::get('/stats', function () {
    $postsCount = Post::count();
    $usersCount = User::count();

    return response()->json([
        'posts' => $postsCount,
        'users' => $usersCount,
        'posts_count' => $postsCount,
        'users_count' => $usersCount,
        'comments_count' => \App\Models\Comment::count(),
    ]);
});

// ── Protected write ────────────────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {
    // posts
    Route::post('/users/{id}/visit', [UserController::class, 'trackVisit']);
    Route::get('/user/notifications', [UserController::class, 'notifications']);
    Route::post('/user/notifications/read', [UserController::class, 'updateNotificationStatus']);
    Route::get('/user/reports', [UserController::class, 'reports']);
    Route::get('/user/following-feed', [UserController::class, 'followingFeed']);
    Route::get('/users/{id}/follow-status', [UserController::class, 'followStatus']);
    Route::get('/users/{id}/favorites', [UserController::class, 'favorites']);
    Route::post('/users/{id}/follow', [UserController::class, 'toggleFollow']);
    Route::post('/posts',          [PostController::class, 'store']);
    Route::put('/posts/{id}',      [PostController::class, 'update']);
    Route::delete('/posts/{id}',   [PostController::class, 'destroy']);
    // comments
    Route::post('/posts/{id}/comments', [CommentController::class, 'store']);
    Route::post('/posts/{id}/report',    [ReportController::class, 'store']);
    Route::delete('/comments/{id}',     [CommentController::class, 'destroy']);
    // likes
    Route::post('/posts/{id}/like',     [LikeController::class, 'toggle']);
    Route::get('/posts/{id}/liked',     [LikeController::class, 'check']);
    Route::get('/likes/batch-check',    [LikeController::class, 'batchCheck']);
    // favorites
    Route::post('/posts/{id}/favorite', [FavoriteController::class, 'toggle']);
    Route::get('/posts/{id}/favorited', [FavoriteController::class, 'check']);
    Route::get('/favorites/batch-check',[FavoriteController::class, 'batchCheck']);
    Route::get('/user/favorites',       [FavoriteController::class, 'userFavorites']);
    // chat
    Route::get('/chat/channel/bootstrap', [ChatController::class, 'bootstrap']);
    Route::get('/chat/channel/messages', [ChatController::class, 'messages']);
    Route::post('/chat/channel/messages', [ChatController::class, 'store']);
    Route::post('/chat/channel/presence', [ChatController::class, 'heartbeat']);
    Route::post('/chat/channel/typing', [ChatController::class, 'typing']);
    Route::get('/chat/channel/settings', [ChatController::class, 'defaultChannelSettings']);
    Route::post('/chat/channel/settings', [ChatController::class, 'updateDefaultChannelSettings']);
    Route::get('/chat/channel/stream-reply', [ChatController::class, 'streamBotReply']);
    Route::get('/chat/channels/{channel}/stream-reply', [ChatController::class, 'streamBotReply']);
    Route::get('/chat/channels/{channel}/bootstrap', [ChatController::class, 'bootstrap']);
    Route::get('/chat/channels/{channel}/messages', [ChatController::class, 'messages']);
    Route::post('/chat/channels/{channel}/messages', [ChatController::class, 'store']);
    Route::post('/chat/channels/{channel}/presence', [ChatController::class, 'heartbeat']);
    Route::post('/chat/channels/{channel}/typing', [ChatController::class, 'typing']);
    Route::get('/chat/channels/{channel}/settings', [ChatController::class, 'settings']);
    Route::post('/chat/channels/{channel}/settings', [ChatController::class, 'updateSettings']);
    Route::post('/chat/channels/{channel}/mute', [ChatController::class, 'mute']);
    Route::delete('/chat/channels/{channel}/mute/{userId}', [ChatController::class, 'unmute']);
    // chat recall & ban
    Route::delete('/chat/channels/{channel}/messages/{messageId}', [ChatController::class, 'recallMessage']);
    Route::get('/chat/channels/{channel}/recalls', [ChatController::class, 'recalls']);
    Route::post('/chat/channels/{channel}/ban', [ChatController::class, 'banUser']);
    Route::delete('/chat/channel/messages/{messageId}', [ChatController::class, 'recallDefaultChannelMessage']);
    Route::get('/chat/channel/recalls', [ChatController::class, 'recalls']);
    Route::post('/chat/channel/ban', [ChatController::class, 'banUser']);
    // TTS
    Route::post('/tts/speak', [TTSController::class, 'speak']);
    Route::get('/tts/voices', [TTSController::class, 'voices']);
    // Model Config
    Route::get('/model-config', [ModelConfigController::class, 'show']);
    Route::put('/model-config', [ModelConfigController::class, 'update']);
    Route::get('/model-config/verify', [ModelConfigController::class, 'verify']);
    // admin
    Route::prefix('admin')->group(function () {
        Route::get('/stats',               [AdminController::class, 'stats']);
        Route::get('/users',               [AdminController::class, 'users']);
        Route::post('/users/{id}/ban',     [AdminController::class, 'banUser']);
        Route::get('/posts',               [AdminController::class, 'posts']);
        Route::patch('/posts/{id}/moderate', [AdminController::class, 'moderatePost']);
        Route::delete('/posts/{id}',       [AdminController::class, 'deletePost']);
        Route::get('/comments',            [AdminController::class, 'comments']);
        Route::delete('/comments/{id}',    [AdminController::class, 'deleteComment']);
        Route::get('/reports',             [AdminController::class, 'reports']);
        Route::get('/reports/{id}',        [AdminController::class, 'reportDetail']);
        Route::patch('/reports/{id}',      [AdminController::class, 'updateReport']);
        Route::get('/announcements',       [AdminController::class, 'announcements']);
        Route::post('/announcements',      [AdminController::class, 'storeAnnouncement']);
        Route::patch('/announcements/{id}',[AdminController::class, 'updateAnnouncement']);
        Route::delete('/announcements/{id}',[AdminController::class, 'deleteAnnouncement']);
        Route::get('/database/records',    [AdminController::class, 'databaseRecords']);
        Route::patch('/database/records/{entity}/{id}', [AdminController::class, 'updateDatabaseRecord']);
        // bot 头像管理
        Route::post('/bot/avatar', [ChatController::class, 'updateBotAvatar']);
        // 内容审核
        Route::get('/moderation/queue', [ModerationController::class, 'index']);
        Route::get('/moderation/stats', [ModerationController::class, 'stats']);
        Route::post('/moderation/{id}/approve', [ModerationController::class, 'approve']);
        Route::post('/moderation/{id}/reject', [ModerationController::class, 'reject']);
        Route::post('/moderation/batch', [ModerationController::class, 'batchAction']);
        // 敏感词管理
        Route::get('/sensitive-words', [SensitiveWordController::class, 'index']);
        Route::post('/sensitive-words', [SensitiveWordController::class, 'store']);
        Route::put('/sensitive-words/{id}', [SensitiveWordController::class, 'update']);
        Route::delete('/sensitive-words/{id}', [SensitiveWordController::class, 'destroy']);
        Route::post('/sensitive-words/{id}/toggle', [SensitiveWordController::class, 'toggleActive']);
        Route::post('/sensitive-words/import', [SensitiveWordController::class, 'import']);
        // 封禁记录管理
        Route::get('/ban-records', [SensitiveWordController::class, 'banRecords']);
        Route::get('/ban-records/stats', [SensitiveWordController::class, 'banStats']);
        Route::post('/ban-records/{id}/unban', [SensitiveWordController::class, 'unbannedUser']);
    });
    // 内容审核预览（所有人可用）
    Route::post('/moderation/preview', [ModerationController::class, 'preview']);
    // 推荐系统
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/recommendations', [RecommendationController::class, 'index']);
        Route::get('/recommendations/profile', [RecommendationController::class, 'profile']);
        Route::post('/recommendations/track', [RecommendationController::class, 'track']);
    });
    // 实时协作
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/collab/drafts', [CollabController::class, 'index']);
        Route::get('/collab/drafts/{id}', [CollabController::class, 'show']);
        Route::post('/collab/drafts', [CollabController::class, 'store']);
        Route::post('/collab/drafts/{id}/join', [CollabController::class, 'join']);
        Route::put('/collab/drafts/{id}', [CollabController::class, 'update']);
        Route::post('/collab/drafts/{id}/cursor', [CollabController::class, 'updateCursor']);
        Route::post('/collab/drafts/{id}/leave', [CollabController::class, 'leave']);
        Route::delete('/collab/drafts/{id}', [CollabController::class, 'destroy']);
        Route::get('/collab/drafts/{id}/stream', [CollabController::class, 'stream']);
    });
});


