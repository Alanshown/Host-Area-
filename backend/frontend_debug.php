<?php
// 创建精确的前端调试脚本
// 这个脚本模拟前端接收到的所有数据

$token = '17|F5ID8ttU5V7xrgkzeovUYo6r0qdIJ5fEk7hFgbx1';
$base = 'http://localhost:8000/api';

// 1. 获取 bootstrap
echo "=== 1. 获取 Bootstrap ===\n";
$ch = curl_init($base . '/chat/channels/public-lobby/bootstrap');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token,
    'Accept: application/json',
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$bootstrap = curl_exec($ch);
curl_close($ch);

$bootstrapData = json_decode($bootstrap, true);
$messages = $bootstrapData['data']['messages'] ?? [];
echo "messages.length: " . count($messages) . "\n";
$msgIds = array_column($messages, 'id');
echo "messages IDs: " . implode(', ', $msgIds) . "\n\n";

// 2. 获取 recalls
echo "=== 2. 获取 Recalls ===\n";
$ch = curl_init($base . '/chat/channels/public-lobby/recalls');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token,
    'Accept: application/json',
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$recallsRaw = curl_exec($ch);
curl_close($ch);

$recallsData = json_decode($recallsRaw, true);
$recalls = $recallsData['data'] ?? [];
echo "recallResponse: $recallsRaw\n\n";

// 3. 模拟前端处理
echo "=== 3. 模拟前端处理 ===\n";

// recalledMessageIds
$recalledIds = [];
foreach ($recalls as $item) {
    $id = isset($item['message_id']) ? intval($item['message_id']) : null;
    if ($id && is_finite($id) && $id > 0) {
        $recalledIds[] = $id;
    }
}
$recalledIdSet = array_flip(array_flip($recalledIds));
echo "recalledMessageIds: " . implode(', ', array_values($recalledIdSet)) . "\n";
echo "recalledMessageIds.size: " . count($recalledIdSet) . "\n\n";

// visibleMessages
$hideBot = false; // 假设
$visibleMessages = [];
foreach ($messages as $msg) {
    $msgId = intval($msg['id']);
    if (isset($recalledIdSet[$msgId])) {
        echo "  过滤掉 (recall): ID=$msgId\n";
        continue;
    }
    if ($hideBot && ($msg['author_role'] ?? '') === 'bot') {
        echo "  过滤掉 (hideBot): ID=$msgId\n";
        continue;
    }
    $visibleMessages[] = $msg;
}
echo "\nvisibleMessages.length: " . count($visibleMessages) . "\n";

// 4. 检查潜在问题
echo "\n=== 4. 潜在问题检查 ===\n";

// 问题 1: recalls 数组是否正确
echo "recalls 类型: " . gettype($recalls) . "\n";
echo "recalls 是否数组: " . (is_array($recalls) ? 'YES' : 'NO') . "\n";

// 问题 2: recalls[0] 是否有 message_id
echo "recalls[0] 存在: " . (isset($recalls[0]) ? 'YES' : 'NO') . "\n";
if (isset($recalls[0])) {
    echo "recalls[0] 有 message_id: " . (isset($recalls[0]['message_id']) ? 'YES' : 'NO') . "\n";
    echo "recalls[0].message_id 值: " . ($recalls[0]['message_id'] ?? 'N/A') . "\n";
}

// 问题 3: recalls 是否有 40 个元素？
echo "recalls.length: " . count($recalls) . "\n";

// 问题 4: 检查 recallResponse?.data ?? [] 的实际行为
$recallResponse = $recallsData;
$fallback = $recallResponse['data'] ?? [];
echo "\nrecallResponse['data'] 类型: " . gettype($fallback) . "\n";
echo "recallResponse['data'] 是否数组: " . (is_array($fallback) ? 'YES' : 'NO') . "\n";
echo "recallResponse['data'] length: " . count($fallback) . "\n";

// 5. 模拟 Vue 的 map + filter + Set
echo "\n=== 5. 模拟 Vue Set ===\n";
$jsStyleRecalled = [];
foreach ($recalls as $item) {
    $msgId = isset($item['message_id']) ? intval($item['message_id']) : null;
    if ($msgId && is_finite($msgId) && $msgId > 0) {
        $jsStyleRecalled[$msgId] = true; // Set 行为
    }
}
echo "JS Set (PHP): " . implode(', ', array_keys($jsStyleRecalled)) . "\n";
echo "Set size: " . count($jsStyleRecalled) . "\n";
