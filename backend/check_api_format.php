<?php
// 测试 API 响应格式，特别是检查 Content-Type
$base = 'http://localhost:8000/api';

// 登录
$ch = curl_init($base . '/auth/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Accept: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['email' => 'shownalan49@gmail.com', 'password' => '123456789']));
$response = curl_exec($ch);
curl_close($ch);
$token = json_decode($response, true)['token'] ?? '';

// 测试 bootstrap，带详细 header
echo "=== 测试 Bootstrap API 详细响应 ===\n";
$ch = curl_init($base . '/chat/channels/public-lobby/bootstrap');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $token",
    'Accept: application/json',
    'X-Requested-With: XMLHttpRequest',
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Content-Type: $contentType\n";
echo "Response Length: " . strlen($response) . " bytes\n";

$data = json_decode($response, true);
if ($data) {
    echo "Response is valid JSON ✓\n";
    echo "Top-level keys: " . implode(', ', array_keys($data)) . "\n";
    if (isset($data['data'])) {
        echo "data keys: " . implode(', ', array_keys($data['data'])) . "\n";
        if (isset($data['data']['messages'])) {
            echo "messages count: " . count($data['data']['messages']) . "\n";
        }
    }
} else {
    echo "Response is NOT valid JSON!\n";
    echo "First 500 chars: " . substr($response, 0, 500) . "\n";
}

// 测试前端 nuxt server 的代理请求
echo "\n=== 测试通过前端 Nuxt 代理 ===\n";
$ch = curl_init('http://localhost:3000/api/chat/channels/public-lobby/bootstrap');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $token",
    'Accept: application/json',
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
if ($httpCode === 200) {
    $data = json_decode($response, true);
    echo "messages count: " . count($data['data']['messages'] ?? []) . "\n";
} else {
    echo "Response: " . substr($response, 0, 200) . "\n";
}
