<?php
// 深度检查 recall 表是否有异常数据
$pdo = new PDO('mysql:host=127.0.0.1;dbname=hostarea', 'hostarea', '123456');

// 检查表结构
echo "=== chat_message_recalls 表结构 ===\n";
$cols = $pdo->query("DESCRIBE chat_message_recalls")->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) echo "  {$c['Field']}: {$c['Type']}\n";

// 检查所有记录
echo "\n=== 所有 recall 记录 ===\n";
$recalls = $pdo->query("SELECT * FROM chat_message_recalls")->fetchAll(PDO::FETCH_ASSOC);
echo "总记录数: " . count($recalls) . "\n";
foreach ($recalls as $r) {
    echo "  ID:{$r['id']}, channel:{$r['channel']}, message_id:{$r['message_id']}, recalled_by:{$r['recalled_by']}\n";
}

// 检查是否有 null 或异常的 message_id
echo "\n=== 检查异常 message_id ===\n";
$stmt = $pdo->query("SELECT * FROM chat_message_recalls WHERE message_id IS NULL OR message_id = ''");
$nullRecalls = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "NULL/空 message_id 记录数: " . count($nullRecalls) . "\n";

// 检查 API 返回的是否和数据库一致
echo "\n=== API 响应 ===\n";
$ch = curl_init('http://localhost:8000/api/chat/channels/public-lobby/recalls');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer 17|F5ID8ttU5V7xrgkzeovUYo6r0qdIJ5fEk7hFgbx1',
]);
$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);
$apiRecalls = $data['data'] ?? [];
echo "API 返回的 recall 数: " . count($apiRecalls) . "\n";
foreach ($apiRecalls as $r) {
    echo "  id:{$r['id']}, message_id:{$r['message_id']}\n";
}

// 检查 recall 的 message_id 是否在 messages 中有对应的消息
echo "\n=== recall.message_id 对应的消息是否存在 ===\n";
$msgIds = $pdo->query("SELECT id FROM chat_messages")->fetchAll(PDO::FETCH_COLUMN);
$msgIdSet = array_flip($msgIds);
foreach ($apiRecalls as $r) {
    $msgId = $r['message_id'];
    $exists = isset($msgIdSet[$msgId]);
    echo "  message_id={$msgId} 存在:" . ($exists ? 'YES' : 'NO') . "\n";
}
