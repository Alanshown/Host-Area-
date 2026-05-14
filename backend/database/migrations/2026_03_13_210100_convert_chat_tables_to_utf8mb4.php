<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ConvertChatTablesToUtf8mb4 extends Migration
{
    public function up()
    {
        // MySQL-only: skip on SQLite (used by in-memory tests)
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        foreach (['chat_messages', 'chat_memories', 'chat_presences'] as $table) {
            if (Schema::hasTable($table)) {
                DB::statement("ALTER TABLE `{$table}` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            }
        }
    }

    public function down()
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        foreach (['chat_messages', 'chat_memories', 'chat_presences'] as $table) {
            if (Schema::hasTable($table)) {
                DB::statement("ALTER TABLE `{$table}` CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci");
            }
        }
    }
}