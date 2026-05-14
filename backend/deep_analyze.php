<?php
// 深入分析：如果 recalls 有 40 个对象，每个对象的 message_id 是什么？
// 假设 recalls = [{message_id: 14}, {message_id: 15}, ..., {message_id: 61}] (40个)
// 那么 recalledMessageIds = Set {14, 15, ..., 61}
// 所有 40 条消息都会被过滤掉！

// 验证：如果 recall.message_id 映射到消息 ID，会发生什么？
$pdo = new PDO('mysql:host=127.0.0.1;dbname=hostarea', 'hostarea', '123456');

// 获取 bootstrap 返回的 40 条消息 ID
$stmt = $pdo->query("SELECT id FROM chat_messages WHERE channel='public-lobby' ORDER BY id DESC LIMIT 40");
$messageIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
sort($messageIds);
echo "Bootstrap 返回的 40 条消息 ID: " . implode(', ', $messageIds) . "\n\n";

// 检查是否有假数据
echo "=== 检查是否有测试数据或假 recall ===\n";

// 模拟：如果 recalls API 返回了错误的数据格式
// 比如返回的是 [{id: 14, channel: ...}, ...] 而不是 [{message_id: 57, ...}]
echo "场景分析:\n";
echo "1. 如果 recall 对象没有 message_id 字段:\n";
echo "   recalledMessageIds = Set { NaN } = size 1\n";
echo "2. 如果 recall.message_id 是消息 ID:\n";
echo "   recalledMessageIds = Set {14, 15, ..., 61} = size 40\n";
echo "   visibleMessages = 40 - 40 = 0\n\n";

// 检查 API 返回格式是否有变化
echo "=== 重新测试 recalls API ===\n";
$ch = curl_init('http://localhost:8000/api/chat/channels/public-lobby/recalls');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer 17|F5ID8ttU5V7xrgkzeovUYo6r0qdIJ5fEk7hFgbx1',
    'Accept: application/json',
]);
$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);
echo "原始响应: $response\n\n";

$recalls = $data['data'] ?? [];
echo "recall 数量: " . count($recalls) . "\n";
foreach ($recalls as $r) {
    echo "  recall: id={$r['id']}, message_id={$r['message_id']}\n";
}

// 检查 recall 的 id 字段
echo "\n=== 检查 recall 的 id 是否与 message_id 混淆 ===\n";
foreach ($recalls as $r) {
    $id = $r['id'];
    $msgId = $r['message_id'];
    echo "recall.id={$id}, recall.message_id={$msgId}\n";
}
