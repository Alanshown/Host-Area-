<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sensitive_words', function (Blueprint $table) {
            $table->id();
            $table->string('word', 100)->comment('敏感词');
            $table->string('category', 50)->default('custom')->comment('分类: custom-自定义, abuse-辱骂, violence-暴力, porn-色情, politics-政治');
            $table->string('level', 20)->default('warning')->comment('级别: warning-警告, mute-禁言, ban-封禁');
            $table->boolean('is_active')->default(true)->comment('是否启用');
            $table->text('description')->nullable()->comment('描述说明');
            $table->timestamps();

            $table->unique('word');
            $table->index(['category', 'is_active']);
            $table->index('is_active');
        });

        Schema::create('user_ban_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->comment('被封禁用户ID');
            $table->unsignedBigInteger('banned_by')->nullable()->comment('封禁操作者ID (0表示系统)');
            $table->string('ban_type', 30)->comment('封禁类型: mute-禁言, ban-封禁账号');
            $table->string('reason', 50)->comment('封禁原因');
            $table->text('detail')->nullable()->comment('详细说明');
            $table->string('source', 30)->default('manual')->comment('来源: manual-手动, auto-自动巡检, report-举报');
            $table->json('evidence')->nullable()->comment('违规证据 (消息ID列表等)');
            $table->timestamp('banned_until')->comment('封禁截止时间');
            $table->timestamp('unbanned_at')->nullable()->comment('解封时间');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['user_id', 'ban_type']);
            $table->index('banned_until');
            $table->index('ban_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_ban_records');
        Schema::dropIfExists('sensitive_words');
    }
};
