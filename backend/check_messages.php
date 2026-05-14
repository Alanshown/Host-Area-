<?php
// 检查 bootstrap API 返回的具体消息内容
$token = '18|l0IKpQqT7JaigywXQCv9r3a0p6ZJy8V5tQ7bRnKf';
$base = 'http://localhost:8000/api';

$ch = curl_init($base . '/chat/channels/public-lobby/bootstrap');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $token",
    'Accept: application/json',
]);
$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);
$messages = $data['data']['messages'] ?? [];

echo "消息总数: " . count($messages) . "\n\n";

// 检查每条消息的必需字段
echo "=== 检查消息必需字段 ===\n";
$issues = [];
for ($i = 0; $i < count($messages); $i++) {
    $msg = $messages[$i];
    $id = $msg['id'] ?? 'MISSING';
    $missing = [];
    if (!isset($msg['id'])) $missing[] = 'id';
    if (!isset($msg['author_role'])) $missing[] = 'author_role';
    if (!isset($msg['message_type'])) $missing[] = 'message_type';
    if (!isset($msg['content'])) $missing[] = 'content';
    if (!isset($msg['author_name'])) $missing[] = 'author_name';
    if (!isset($msg['channel'])) $missing[] = 'channel';
    if ($missing) {
        $issues[] = "消息 $id 缺少字段: " . implode(', ', $missing);
    }
}

if ($issues) {
    echo "发现问题:\n";
    foreach ($issues as $issue) {
        echo "  $issue\n";
    }
} else {
    echo "所有消息都有必需字段 ✓\n";
}

// 检查是否有 null 值
echo "\n=== 检查 null 值 ===\n";
$nullCount = 0;
for ($i = 0; $i < count($messages); $i++) {
    $msg = $messages[$i];
    foreach ($msg as $key => $value) {
        if ($value === null) {
            $nullCount++;
            if ($nullCount <= 5) {
                echo "消息 {$msg['id']} 的 '$key' 是 null\n";
            }
        }
    }
}
echo "总共 null 值数量: $nullCount\n";

// 显示前3条和后3条消息的完整结构
echo "\n=== 前3条消息 ===\n";
for ($i = 0; $i < min(3, count($messages)); $i++) {
    echo "消息 " . ($i+1) . ":\n";
    print_r($messages[$i]);
}

echo "\n=== 后3条消息 ===\n";
$start = max(0, count($messages) - 3);
for ($i = $start; $i < count($messages); $i++) {
    echo "消息 " . ($i+1) . ":\n";
    print_r($messages[$i]);
}
