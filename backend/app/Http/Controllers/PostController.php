<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Services\ContentModerationService;
use App\Services\RecommendationService;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;

class PostController extends Controller
{
    protected $moderationService;
    protected $recommendationService;

    public function __construct(ContentModerationService $moderationService, RecommendationService $recommendationService)
    {
        $this->moderationService = $moderationService;
        $this->recommendationService = $recommendationService;
    }

    public function index(Request $request)
    {
        $sort       = $request->query('sort', 'latest');
        $search     = $request->query('search', '');
        $categoryId = $request->query('category_id');

        $query = Post::with(['user:id,username,avatar', 'category:id,name'])
            ->withCount('comments')
            ->where(function ($q) {
                $q->where('review_status', 'approved')
                    ->orWhere('review_status', 'auto_passed');
            });

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
        }

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        if ($sort === 'hot') {
            $query->orderBy('likes', 'desc');
        } else {
            $query->latest();
        }

        return response()->json($query->paginate(10));
    }

    public function hot()
    {
        $posts = Post::with(['user:id,username'])
            ->withCount('comments')
            ->where(function ($q) {
                $q->where('review_status', 'approved')
                    ->orWhere('review_status', 'auto_passed');
            })
            ->orderBy('likes', 'desc')
            ->take(10)
            ->get(['id', 'title', 'likes', 'user_id', 'created_at']);

        return response()->json(['data' => $posts]);
    }

    public function show($id)
    {
        $post = Post::with(['user:id,username,avatar,bio', 'category:id,name'])
            ->withCount('comments')
            ->findOrFail($id);

        if (!in_array($post->review_status, ['approved', 'auto_passed'])) {
            throw new NotFoundHttpException();
        }

        $post->increment('views');

        // Track view for recommendation
        if ($user = auth()->user()) {
            $this->recommendationService->trackInteraction(
                $user->id,
                (string) $post->category_id,
                'view',
                $this->extractTags($post->title . ' ' . $post->content)
            );
        }

        return response()->json(['data' => $post]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'       => 'required|string|min:5|max:255',
            'content'     => 'required|string|min:10',
            'category_id' => 'required|integer|exists:categories,id',
        ]);

        if ($request->hasFile('cover_image')) {
            $this->assertValidImage($request->file('cover_image'), 'cover_image', 6144);
        }

        if ($request->hasFile('cover_image')) {
            $validated['cover_image'] = $this->storeCover($request->file('cover_image'));
        }

        $validated['user_id'] = $request->user()->id;

        // AI-powered content moderation
        $moderation = $this->moderationService->analyzeContent(
            $validated['title'] . "\n\n" . $validated['content'],
            'post'
        );

        if (!$moderation['passed']) {
            // Content violated rules, add to moderation queue
            \DB::table('moderation_queue')->insert([
                'content_type' => 'post',
                'content' => $validated['title'] . "\n\n" . $validated['content'],
                'user_id' => $request->user()->id,
                'status' => 'pending',
                'category' => implode(',', array_column($moderation['scores'], 'name')),
                'score' => $moderation['totalScore'],
                'reason' => $moderation['reason'],
                'analysis' => json_encode($moderation),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $validated['review_status'] = 'pending';
            $validated['review_reason'] = $moderation['reason'];
        } elseif (isset($moderation['requires_review'])) {
            // Flagged for manual review
            \DB::table('moderation_queue')->insert([
                'content_type' => 'post',
                'content' => $validated['title'] . "\n\n" . $validated['content'],
                'user_id' => $request->user()->id,
                'status' => 'pending',
                'category' => implode(',', array_column($moderation['scores'], 'name')),
                'score' => $moderation['totalScore'],
                'reason' => $moderation['reason'],
                'analysis' => json_encode($moderation),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $validated['review_status'] = 'pending';
            $validated['review_reason'] = '待人工审核';
        } else {
            $validated['review_status'] = 'auto_passed';
            $validated['review_reason'] = null;
        }

        $post = Post::create($validated);
        $post->load(['user:id,username,avatar', 'category:id,name']);
        $post->loadCount('comments');

        // Track for recommendations
        if ($moderation['passed']) {
            $this->recommendationService->trackInteraction(
                $request->user()->id,
                (string) $validated['category_id'],
                'post',
                $this->extractTags($validated['title'] . ' ' . $validated['content'])
            );
        }

        return response()->json([
            'data' => $post,
            'moderation' => [
                'passed' => $moderation['passed'],
                'message' => $moderation['passed']
                    ? ($post->review_status === 'pending' ? '帖子已提交，等待审核' : '发布成功')
                    : '内容可能包含违规信息，已提交审核',
            ]
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $post = Post::findOrFail($id);

        if ($post->user_id !== $request->user()->id && $request->user()->role !== 'admin') {
            return response()->json(['message' => '无权限修改此帖子'], 403);
        }

        $validated = $request->validate([
            'title'       => 'sometimes|string|min:5|max:255',
            'content'     => 'sometimes|string|min:10',
            'category_id' => 'sometimes|integer|exists:categories,id',
        ]);

        if ($request->hasFile('cover_image')) {
            $this->assertValidImage($request->file('cover_image'), 'cover_image', 6144);
        }

        if ($request->hasFile('cover_image')) {
            $this->deleteCover($post->cover_image);
            $validated['cover_image'] = $this->storeCover($request->file('cover_image'));
        }

        if (array_key_exists('content', $validated) || array_key_exists('title', $validated)) {
            // Re-run moderation on content changes
            $content = ($validated['title'] ?? $post->title) . "\n\n" . ($validated['content'] ?? $post->content);
            $moderation = $this->moderationService->analyzeContent($content, 'post');

            if (!$moderation['passed']) {
                $validated['review_status'] = 'pending';
                $validated['review_reason'] = $moderation['reason'];

                \DB::table('moderation_queue')->updateOrInsert(
                    ['content_type' => 'post', 'content_id' => $id],
                    [
                        'content' => $content,
                        'user_id' => $post->user_id,
                        'status' => 'pending',
                        'category' => implode(',', array_column($moderation['scores'], 'name')),
                        'score' => $moderation['totalScore'],
                        'reason' => $moderation['reason'],
                        'analysis' => json_encode($moderation),
                        'updated_at' => now(),
                    ]
                );
            } else {
                $validated['review_status'] = 'auto_passed';
                $validated['review_reason'] = null;
            }
        }

        $post->update($validated);
        $post->load(['user:id,username,avatar', 'category:id,name']);
        $post->loadCount('comments');

        return response()->json(['data' => $post]);
    }

    public function destroy(Request $request, $id)
    {
        $post = Post::findOrFail($id);

        if ($post->user_id !== $request->user()->id && $request->user()->role !== 'admin') {
            return response()->json(['message' => '无权限删除此帖子'], 403);
        }

        $this->deleteCover($post->cover_image);
        $post->delete();
        return response()->json(['message' => '已删除']);
    }

    private function storeCover($file)
    {
        $dir = public_path('uploads/covers');
        if (!File::exists($dir)) {
            File::makeDirectory($dir, 0755, true);
        }
        $name = uniqid('cover_') . '.' . $file->getClientOriginalExtension();
        $file->move($dir, $name);
        return '/uploads/covers/' . $name;
    }

    private function deleteCover($path)
    {
        if (!$path || strpos($path, '/uploads/') !== 0) return;
        $full = public_path(ltrim($path, '/'));
        if (File::exists($full)) File::delete($full);
    }

    private function assertValidImage($file, string $field, int $maxKilobytes): void
    {
        if (! $file || ! $file->isValid()) {
            throw ValidationException::withMessages([
                $field => ['上传文件无效，请重新选择图片。'],
            ]);
        }

        if (($file->getSize() ?? 0) > ($maxKilobytes * 1024)) {
            throw ValidationException::withMessages([
                $field => [sprintf('图片大小不能超过 %d MB。', (int) ceil($maxKilobytes / 1024))],
            ]);
        }

        $extension = strtolower($file->getClientOriginalExtension() ?: '');
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];

        if (! in_array($extension, $allowedExtensions, true)) {
            throw ValidationException::withMessages([
                $field => ['仅支持 jpg、jpeg、png、gif、webp、bmp 格式图片。'],
            ]);
        }

        if (! @getimagesize($file->getRealPath())) {
            throw ValidationException::withMessages([
                $field => ['上传文件不是可识别的图片。'],
            ]);
        }
    }

    /**
     * Extract tags/keywords from content
     */
    private function extractTags(string $content): array
    {
        // Simple keyword extraction (in production, use NLP)
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
