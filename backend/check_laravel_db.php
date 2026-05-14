<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Laravel 数据库配置:\n";
echo "  Database: " . config('database.connections.mysql.database') . "\n";
echo "  Host: " . config('database.connections.mysql.host') . "\n";
echo "  Username: " . config('database.connections.mysql.username') . "\n";

// 测试查询
use App\Models\ChatMessage;

$count = ChatMessage::where('channel', 'public-lobby')->count();
echo "\n使用 Laravel ORM 查询 public-lobby 消息数: $count\n";

$allCount = ChatMessage::count();
echo "总消息数: $allCount\n";
