<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Throwable;

class RestoreHostareaDump extends Command
{
    protected $signature = 'hostarea:restore-dump {file? : Path to the SQL dump file} {--force : Skip confirmation}';

    protected $description = 'Restore users and post-related content from a legacy Hostarea SQL dump into the current database';

    private array $tablesToImport = [
        'categories',
        'users',
        'posts',
        'comments',
        'likes',
        'favorites',
        'reports',
        'profile_visits',
    ];

    private array $tablesToClear = [
        'chat_message_recalls',
        'chat_channel_mutes',
        'chat_channel_preferences',
        'chat_presences',
        'chat_memories',
        'chat_messages',
        'user_notification_statuses',
        'user_follows',
        'user_bans',
        'personal_access_tokens',
        'profile_visits',
        'reports',
        'favorites',
        'likes',
        'comments',
        'posts',
        'announcements',
        'users',
        'categories',
    ];

    public function handle(): int
    {
        $path = $this->argument('file') ?: dirname(base_path()) . DIRECTORY_SEPARATOR . 'hostarea_main.sql';

        if (! File::exists($path)) {
            $this->error("SQL 文件不存在: {$path}");
            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm("这会覆盖当前数据库中的用户和帖子相关数据，是否继续？", false)) {
            $this->warn('已取消恢复。');
            return self::INVALID;
        }

        $sql = File::get($path);
        $statements = $this->extractInsertStatements($sql);

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            foreach ($this->tablesToClear as $table) {
                if (! Schema::hasTable($table)) {
                    continue;
                }

                DB::statement("TRUNCATE TABLE `{$table}`");
                $this->line("已清空 {$table}");
            }

            foreach ($this->tablesToImport as $table) {
                if (! isset($statements[$table])) {
                    $this->warn("SQL 中未找到 {$table} 的 INSERT 语句，已跳过");
                    continue;
                }

                DB::unprepared($statements[$table]);
                $count = DB::table($table)->count();
                $this->info("已恢复 {$table}，当前 {$count} 条");
            }
        } catch (Throwable $exception) {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            $this->error('恢复失败: ' . $exception->getMessage());
            return self::FAILURE;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->newLine();
        foreach ($this->tablesToImport as $table) {
            if (Schema::hasTable($table)) {
                $this->line("{$table}: " . DB::table($table)->count());
            }
        }

        $this->info('数据恢复完成。');
        return self::SUCCESS;
    }

    private function extractInsertStatements(string $sql): array
    {
        $statements = [];

        foreach ($this->tablesToImport as $table) {
            $pattern = sprintf('/INSERT INTO `%s`\s*\([^;]+?;(?=\r?\n\r?\n|\z)/s', preg_quote($table, '/'));
            if (preg_match($pattern, $sql, $matches) === 1) {
                $statements[$table] = trim($matches[0]);
            }
        }

        return $statements;
    }
}