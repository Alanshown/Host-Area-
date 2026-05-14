<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddModerationColumnsToPostsTable extends Migration
{
    public function up()
    {
        Schema::table('posts', function (Blueprint $table) {
            if (! Schema::hasColumn('posts', 'moderation_status')) {
                $table->string('moderation_status', 32)->default('approved')->after('likes');
            }

            if (! Schema::hasColumn('posts', 'moderation_note')) {
                $table->string('moderation_note')->nullable()->after('moderation_status');
            }

            if (! Schema::hasColumn('posts', 'moderated_by')) {
                $table->foreignId('moderated_by')->nullable()->after('moderation_note')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('posts', 'moderated_at')) {
                $table->timestamp('moderated_at')->nullable()->after('moderated_by');
            }
        });

        DB::table('posts')
            ->whereNull('moderation_status')
            ->update([
                'moderation_status' => 'approved',
                'moderation_note' => '历史帖子已迁移为通过状态。',
                'moderated_at' => now(),
            ]);
    }

    public function down()
    {
        Schema::table('posts', function (Blueprint $table) {
            if (Schema::hasColumn('posts', 'moderated_by')) {
                $table->dropConstrainedForeignId('moderated_by');
            }

            $columns = array_values(array_filter([
                Schema::hasColumn('posts', 'moderation_status') ? 'moderation_status' : null,
                Schema::hasColumn('posts', 'moderation_note') ? 'moderation_note' : null,
                Schema::hasColumn('posts', 'moderated_at') ? 'moderated_at' : null,
            ]));

            if ($columns) {
                $table->dropColumn($columns);
            }
        });
    }
}