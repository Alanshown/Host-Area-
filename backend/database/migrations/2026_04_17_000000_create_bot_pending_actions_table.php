<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bot_pending_actions', function (Blueprint $table) {
            $table->id();
            $table->string('channel', 64)->default('public-lobby')->index();
            $table->foreignId('actor_id')->constrained('users')->cascadeOnDelete();
            $table->string('action_type', 32); // 'ban_confirmation'
            $table->json('payload');            // { target_user_id, target_username, ... }
            $table->timestamp('expires_at');     // 过期时间，防止状态悬空
            $table->timestamps();

            $table->unique(['channel', 'actor_id', 'action_type'], 'bot_pending_unique');
            $table->index(['expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_pending_actions');
    }
};
