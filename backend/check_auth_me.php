<?php
// 检查 auth/me API 响应
$base = 'http://localhost:8000/api';

// 使用最新的 token
$ch = curl_init($base . '/auth/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Accept: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['email' => 'shownalan49@gmail.com', 'password' => '123456789']));
$response = curl_exec($ch);
curl_close($ch);

$loginData = json_decode($response, true);
$token = $loginData['token'] ?? '';
echo "Token: " . substr($token, 0, 30) . "...\n\n";

// 测试 auth/me
echo "=== 测试 auth/me ===\n";
$ch = curl_init($base . '/auth/me');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $token",
    'Accept: application/json',
]);
$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);
echo "auth/me 响应:\n";
if (isset($data['user'])) {
    echo "  user: " . json_encode($data['user']) . "\n";
} else {
    echo "  data: " . json_encode($data) . "\n";
}
