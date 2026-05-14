<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('moderation_queue', function (Blueprint $table) {
            $table->id();
            $table->string('content_type', 32); // post, comment, chat_message
            $table->unsignedBigInteger('content_id')->nullable(); // Foreign key to the content
            $table->text('content'); // Content to moderate
            $table->unsignedBigInteger('user_id')->nullable(); // User who posted
            $table->string('status', 32)->default('pending'); // pending, approved, rejected
            $table->string('category', 64)->nullable(); // Violation category
            $table->decimal('score', 3, 3)->default(0); // Violation score 0-1
            $table->text('reason')->nullable(); // Moderation reason
            $table->json('analysis')->nullable(); // Full analysis result
            $table->unsignedBigInteger('moderator_id')->nullable(); // Admin who reviewed
            $table->timestamp('moderated_at')->nullable(); // When reviewed
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('content_type');
        });

        // User interests/tags table
        Schema::create('user_interests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('category_id')->nullable(); // Category the user engaged with
            $table->string('tag', 64); // Extracted interest tag
            $table->string('source', 32)->default('post'); // post, comment, like, favorite
            $table->unsignedInteger('weight')->default(1); // Interaction weight
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['user_id', 'tag', 'source']);
            $table->index(['user_id', 'weight']);
        });

        // Collab draft table for real-time collaboration
        Schema::create('collab_drafts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('post_id')->nullable();
            $table->unsignedBigInteger('owner_id'); // Who started the draft
            $table->text('content')->nullable();
            $table->string('channel', 64)->default('public-lobby');
            $table->json('cursors')->nullable(); // {userId: {line, column, selection}}
            $table->json('participants')->nullable(); // [{userId, username, joined_at}]
            $table->timestamp('expires_at')->nullable(); // Auto-cleanup old drafts
            $table->timestamps();

            $table->foreign('post_id')->references('id')->on('posts')->onDelete('cascade');
            $table->foreign('owner_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['channel', 'expires_at']);
            $table->index('owner_id');
        });

        // Add is_reviewed column to posts table
        Schema::table('posts', function (Blueprint $table) {
            $table->string('review_status', 32)->default('auto_passed')->after('category_id');
            $table->text('review_reason')->nullable()->after('review_status');
            $table->timestamp('reviewed_at')->nullable()->after('review_reason');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn(['review_status', 'review_reason', 'reviewed_at']);
        });
        Schema::dropIfExists('moderation_queue');
        Schema::dropIfExists('user_interests');
        Schema::dropIfExists('collab_drafts');
    }
};
