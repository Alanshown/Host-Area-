<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1. users 表加 banned_until 字段
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('banned_until')->nullable()->after('role');
            $table->string('ban_reason', 255)->nullable()->after('banned_until');
        });

        // 2. 用户封禁记录表
        Schema::create('user_bans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('banned_by');
            $table->string('reason', 255)->default('');
            $table->timestamp('banned_until')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('banned_by')->references('id')->on('users')->cascadeOnDelete();
            $table->index(['user_id', 'banned_until']);
        });

        // 3. 消息撤回记录表
        Schema::create('chat_message_recalls', function (Blueprint $table) {
            $table->id();
            $table->string('channel', 64);
            $table->unsignedBigInteger('message_id');
            $table->unsignedBigInteger('recalled_by');
            $table->string('original_author_name', 100)->default('');
            $table->unsignedBigInteger('original_author_id')->nullable();
            $table->timestamps();

            $table->foreign('recalled_by')->references('id')->on('users')->cascadeOnDelete();
            $table->index(['channel', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_message_recalls');
        Schema::dropIfExists('user_bans');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['banned_until', 'ban_reason']);
        });
    }
};
