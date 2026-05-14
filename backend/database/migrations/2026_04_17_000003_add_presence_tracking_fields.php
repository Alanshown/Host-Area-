<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_presences', function (Blueprint $table) {
            $table->timestamp('first_seen_at')->nullable()->after('typing_updated_at');
            $table->unsignedInteger('message_count_today')->default(0)->after('first_seen_at');
        });
    }

    public function down(): void
    {
        Schema::table('chat_presences', function (Blueprint $table) {
            $table->dropColumn(['first_seen_at', 'message_count_today']);
        });
    }
};
