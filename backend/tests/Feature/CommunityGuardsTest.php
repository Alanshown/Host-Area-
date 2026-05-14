<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Favorite;
use App\Models\Post;
use App\Models\User;
use App\Models\UserFollow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CommunityGuardsTest extends TestCase
{
    use RefreshDatabase;

    public function test_normal_user_long_post_enters_pending_moderation(): void
    {
        $user = User::factory()->create();
        $category = Category::query()->create([
            'name' => 'AI',
            'description' => 'AI discussion',
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/posts', [
            'title' => '这是一篇需要审核的长帖标题',
            'content' => str_repeat('这是需要审核的正文内容。', 6),
            'category_id' => $category->id,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.moderation_status', 'pending');

        $this->assertDatabaseHas('posts', [
            'title' => '这是一篇需要审核的长帖标题',
            'moderation_status' => 'pending',
        ]);
    }

    public function test_user_cannot_read_other_users_favorites(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        $category = Category::query()->create([
            'name' => 'Web',
            'description' => 'Web discussion',
        ]);

        $post = Post::query()->create([
            'title' => '公开帖子标题',
            'content' => '公开帖子正文内容',
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'moderation_status' => 'approved',
        ]);

        Favorite::query()->create([
            'user_id' => $owner->id,
            'post_id' => $post->id,
        ]);

        Sanctum::actingAs($viewer);

        $this->getJson("/api/users/{$owner->id}/favorites")
            ->assertForbidden();

        Sanctum::actingAs($owner);

        $this->getJson("/api/users/{$owner->id}/favorites")
            ->assertOk()
            ->assertJsonFragment(['id' => $post->id]);
    }

    public function test_comment_reply_parent_must_belong_to_same_post(): void
    {
        $user = User::factory()->create();
        $author = User::factory()->create();
        $category = Category::query()->create([
            'name' => 'Backend',
            'description' => 'Backend discussion',
        ]);

        $postA = Post::query()->create([
            'title' => '帖子 A 标题',
            'content' => '帖子 A 正文内容',
            'user_id' => $author->id,
            'category_id' => $category->id,
            'moderation_status' => 'approved',
        ]);

        $postB = Post::query()->create([
            'title' => '帖子 B 标题',
            'content' => '帖子 B 正文内容',
            'user_id' => $author->id,
            'category_id' => $category->id,
            'moderation_status' => 'approved',
        ]);

        $parentComment = Comment::query()->create([
            'post_id' => $postB->id,
            'user_id' => $author->id,
            'content' => '这是 B 帖子的父评论',
            'parent_id' => null,
        ]);

        Sanctum::actingAs($user);

        $this->postJson("/api/posts/{$postA->id}/comments", [
            'content' => '尝试跨帖回复',
            'parent_id' => $parentComment->id,
        ])
            ->assertStatus(422)
            ->assertJsonFragment(['message' => '回复的目标评论不属于当前帖子']);
    }

    public function test_following_feed_returns_only_followed_users_approved_posts(): void
    {
        $viewer = User::factory()->create();
        $followedAuthor = User::factory()->create();
        $otherAuthor = User::factory()->create();
        $category = Category::query()->create([
            'name' => 'Frontend',
            'description' => 'Frontend discussion',
        ]);

        UserFollow::query()->create([
            'follower_id' => $viewer->id,
            'followed_user_id' => $followedAuthor->id,
        ]);

        $approvedFollowedPost = Post::query()->create([
            'title' => '关注作者的公开帖子',
            'content' => '公开内容',
            'user_id' => $followedAuthor->id,
            'category_id' => $category->id,
            'moderation_status' => 'approved',
        ]);

        Post::query()->create([
            'title' => '关注作者的待审核帖子',
            'content' => '待审核内容',
            'user_id' => $followedAuthor->id,
            'category_id' => $category->id,
            'moderation_status' => 'pending',
        ]);

        Post::query()->create([
            'title' => '未关注作者的公开帖子',
            'content' => '别人的公开内容',
            'user_id' => $otherAuthor->id,
            'category_id' => $category->id,
            'moderation_status' => 'approved',
        ]);

        Sanctum::actingAs($viewer);

        $response = $this->getJson('/api/user/following-feed');

        $response->assertOk()
            ->assertJsonFragment(['id' => $approvedFollowedPost->id])
            ->assertJsonMissing(['title' => '关注作者的待审核帖子'])
            ->assertJsonMissing(['title' => '未关注作者的公开帖子']);
    }

    public function test_notifications_support_read_and_unread_state(): void
    {
        $owner = User::factory()->create();
        $commenter = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);
        $category = Category::query()->create([
            'name' => 'Notify',
            'description' => 'Notify discussion',
        ]);

        $post = Post::query()->create([
            'title' => '通知测试帖子',
            'content' => '通知测试正文',
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'moderation_status' => 'approved',
        ]);

        $comment = Comment::query()->create([
            'post_id' => $post->id,
            'user_id' => $commenter->id,
            'content' => '这里是一条新的评论提醒',
        ]);

        Announcement::query()->create([
            'title' => '维护公告',
            'body' => '今晚会有短暂维护，请提前保存内容。',
            'is_active' => true,
            'published_by' => $admin->id,
        ]);

        Sanctum::actingAs($owner);

        $listResponse = $this->getJson('/api/user/notifications');

        $listResponse->assertOk()
            ->assertJsonPath('summary.unread', 2)
            ->assertJsonFragment(['id' => 'comment-' . $comment->id])
            ->assertJsonFragment(['is_read' => false]);

        $this->postJson('/api/user/notifications/read', [
            'ids' => ['comment-' . $comment->id],
            'read' => true,
        ])->assertOk();

        $afterRead = $this->getJson('/api/user/notifications');

        $afterRead->assertOk()
            ->assertJsonPath('summary.unread', 1)
            ->assertJsonFragment([
                'id' => 'comment-' . $comment->id,
                'is_read' => true,
            ]);

        $this->postJson('/api/user/notifications/read', [
            'ids' => ['comment-' . $comment->id],
            'read' => false,
        ])->assertOk();

        $afterUnread = $this->getJson('/api/user/notifications');

        $afterUnread->assertOk()
            ->assertJsonPath('summary.unread', 2)
            ->assertJsonFragment([
                'id' => 'comment-' . $comment->id,
                'is_read' => false,
            ]);
    }
}