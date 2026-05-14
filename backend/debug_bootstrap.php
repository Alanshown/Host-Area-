<?php
// 生成测试 Token
$user = \App\Models\User::find(11);
$token = $user->createToken('test')->plainTextToken;
echo "Token: " . $token . PHP_EOL;

// 直接调用 bootstrap 并查看返回结构
$chatBot = app(\App\Services\ChatBotService::class);
$result = $chatBot->bootstrap('public-lobby');

echo PHP_EOL . "=== Bootstrap Result ===" . PHP_EOL;
echo "messages count: " . count($result['messages']) . PHP_EOL;
echo "members count: " . count($result['members']) . PHP_EOL;
echo "channel: " . $result['channel'] . PHP_EOL;

if (!empty($result['messages'])) {
    echo PHP_EOL . "First message:" . PHP_EOL;
    print_r($result['messages'][0]);
}

// 测试序列化
$msg = \App\Models\ChatMessage::where('channel', 'public-lobby')->first();
if ($msg) {
    echo PHP_EOL . "=== Serialization Test ===" . PHP_EOL;
    echo "Original message ID: " . $msg->id . PHP_EOL;
    echo "Author: " . $msg->author_name . PHP_EOL;
    $serialized = $chatBot->serializeMessage($msg);
    echo "Serialized ID: " . $serialized['id'] . PHP_EOL;
    echo "Serialized author_name: " . $serialized['author_name'] . PHP_EOL;
    echo "Serialized content length: " . strlen($serialized['content'] ?? '') . PHP_EOL;
}
