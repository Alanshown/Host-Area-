<?php
// 使用正确的数据库凭据
$host = '127.0.0.1';
$dbname = 'hostarea';
$username = 'hostarea';
$password = '123456';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "=== 直接检查数据库 ===\n\n";

    // 1. 检查表
    $stmt = $pdo->query("SHOW TABLES LIKE 'chat_messages'");
    if ($stmt->rowCount() > 0) {
        echo "✓ chat_messages 表存在\n";
    } else {
        echo "✗ chat_messages 表不存在\n";
    }

    // 2. 检查记录数
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM chat_messages");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "chat_messages 总记录数: " . $row['cnt'] . "\n";

    // 3. 检查 public-lobby 频道
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM chat_messages WHERE channel = 'public-lobby'");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "public-lobby 消息数: " . $row['cnt'] . "\n";

    // 4. 查看所有 channel 值
    $stmt = $pdo->query("SELECT DISTINCT channel FROM chat_messages");
    echo "\n数据库中的所有频道:\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $channel = trim($row['channel']);
        echo "  - '$channel'\n";
    }

    // 5. 最近10条消息
    $stmt = $pdo->query("SELECT id, channel, author_name, author_role FROM chat_messages ORDER BY id DESC LIMIT 10");
    echo "\n最近10条消息:\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "  ID={$row['id']}, channel='{$row['channel']}', author={$row['author_name']}\n";
    }

    // 6. 直接模拟 Laravel 查询
    echo "\n=== 模拟 Laravel bootstrap 查询 ===\n";
    $stmt = $pdo->query("
        SELECT id, channel, author_name, author_role, message_type
        FROM chat_messages
        WHERE channel = 'public-lobby'
        ORDER BY id DESC
        LIMIT 40
    ");
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Laravel 查询应返回: " . count($messages) . " 条消息\n";

    if (count($messages) > 0) {
        $reversed = array_reverse($messages);
        echo "第一条消息: ID={$reversed[0]['id']}\n";
        echo "最后条消息: ID={$reversed[count($reversed)-1]['id']}\n";
    }

} catch (PDOException $e) {
    echo "数据库错误: " . $e->getMessage() . "\n";
}
