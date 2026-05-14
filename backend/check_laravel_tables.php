<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\ChatMessage;

// 1. 检查 Laravel 使用的数据库连接
echo "=== Laravel 数据库配置 ===\n";
echo "Connection: " . config('database.default') . "\n";
echo "Database: " . config('database.connections.mysql.database') . "\n";
echo "Host: " . config('database.connections.mysql.host') . "\n\n";

// 2. 检查 chat_messages 表是否存在
echo "=== 表检查 ===\n";
try {
    $tables = DB::select('SHOW TABLES');
    echo "数据库中的表:\n";
    foreach ($tables as $table) {
        $tableName = array_values((array)$table)[0];
        if (str_contains(strtolower($tableName), 'chat') || str_contains(strtolower($tableName), 'message') || str_contains(strtolower($tableName), 'hall')) {
            echo "  ✓ $tableName\n";
        }
    }
} catch (Exception $e) {
    echo "错误: " . $e->getMessage() . "\n";
}

echo "\n=== ChatMessage 模型测试 ===\n";
try {
    $total = ChatMessage::count();
    echo "ChatMessage::count() = $total\n";

    $lobby = ChatMessage::where('channel', 'public-lobby')->count();
    echo "ChatMessage::where('channel', 'public-lobby')->count() = $lobby\n";

    if ($lobby > 0) {
        $latest = ChatMessage::where('channel', 'public-lobby')->latest('id')->first();
        echo "最新消息: ID={$latest->id}, channel={$latest->channel}, author={$latest->author_name}\n";
    }
} catch (Exception $e) {
    echo "ChatMessage 查询错误: " . $e->getMessage() . "\n";
}

// 3. 尝试直接查询 SQL
echo "\n=== 直接 SQL 查询 ===\n";
try {
    $result = DB::select("SELECT COUNT(*) as cnt, `channel` FROM chat_messages GROUP BY `channel`");
    foreach ($result as $row) {
        echo "channel={$row->channel}, count={$row->cnt}\n";
    }
} catch (Exception $e) {
    echo "SQL 错误: " . $e->getMessage() . "\n";
}

// 4. 检查是否有表前缀
echo "\n=== 表前缀检查 ===\n";
echo "Laravel table prefix: " . (config('database.connections.mysql.prefix') ?: 'none') . "\n";
