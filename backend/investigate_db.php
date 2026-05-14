<?php
// 测试 bootstrap API 的完整响应
$pdo = new PDO('mysql:host=127.0.0.1;dbname=hostarea', 'hostarea', '123456');

// 1. 检查 bootstrap 会返回哪些消息
echo "=== Bootstrap 会返回哪些消息 (take 40, latest, reverse) ===\n";
$stmt = $pdo->query("SELECT id, author_role, message_type FROM chat_messages WHERE channel='public-lobby' ORDER BY id DESC LIMIT 40");
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "返回的消息数: " . count($messages) . "\n";
$ids = array_column($messages, 'id');
sort($ids);
echo "消息 ID 范围: " . min($ids) . " - " . max($ids) . "\n";
echo "所有消息 ID: " . implode(', ', $ids) . "\n\n";

// 2. 检查 recall 中的 message_id 是否在这些消息中
echo "=== 检查撤回记录 ===\n";
$stmt = $pdo->query("SELECT * FROM chat_message_recalls");
$recalls = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($recalls as $r) {
    $inBootstrap = in_array($r['message_id'], $ids);
    echo "recall: message_id={$r['message_id']}, 在bootstrap中:" . ($inBootstrap ? 'YES' : 'NO') . "\n";
}

// 3. 模拟 visibleMessages 过滤逻辑
echo "\n=== 模拟前端 visibleMessages 过滤 ===\n";
$recalledIds = array_column($recalls, 'message_id');
echo "被撤回的 message_ids: " . implode(', ', $recalledIds) . "\n";
$hideBot = false; // 假设
$visible = 0;
$filteredByRecall = 0;
$filteredByHideBot = 0;
foreach ($messages as $m) {
    $id = (int)$m['id'];
    if (in_array($id, $recalledIds)) {
        $filteredByRecall++;
        continue;
    }
    if ($hideBot && $m['author_role'] === 'bot') {
        $filteredByHideBot++;
        continue;
    }
    $visible++;
}
echo "过滤后 visibleMessages 数: $visible\n";
echo "被撤回过滤掉: $filteredByRecall\n";
echo "被 hideBot 过滤掉: $filteredByHideBot\n";

// 4. 检查 recall.message_id=57 是否在 bootstrap 返回的 40 条消息中
echo "\n=== 检查 recall message_id=57 是否在返回的40条消息中 ===\n";
echo "57 in IDs: " . (in_array(57, $ids) ? 'YES' : 'NO') . "\n";
// 如果 57 不在返回的 40 条中，那么 visibleMessages 应该 = 40

// 5. 检查是否有其他问题
echo "\n=== 完整数据库消息 ID ===\n";
$all = $pdo->query("SELECT id FROM chat_messages WHERE channel='public-lobby' ORDER BY id")->fetchAll(PDO::FETCH_COLUMN);
echo "所有消息 ID: " . implode(', ', $all) . "\n";
echo "总数: " . count($all) . "\n";

// 6. 检查 bootstrap 的实际 API 返回（模拟）
echo "\n=== 实际 bootstrap API 模拟 ===\n";
$stmt = $pdo->query("SELECT * FROM chat_messages WHERE channel='public-lobby' ORDER BY id DESC LIMIT 40");
$bootstrapMessages = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "API 会返回: " . count($bootstrapMessages) . " 条消息\n";
// 反转
$reversed = array_reverse($bootstrapMessages);
$firstIds = array_slice(array_column($reversed, 'id'), 0, 5);
$lastIds = array_slice(array_column($reversed, 'id'), -5);
echo "反转后第一条: " . implode(', ', $firstIds) . "\n";
echo "反转后最后条: " . implode(', ', $lastIds) . "\n";
