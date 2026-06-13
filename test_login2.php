<?php
// 测试登录API

$url = 'http://localhost:8000/api/v1/login';
$data = ['email' => 'admin@erp.com', 'password' => 'admin123'];

echo "Testing login API...\n";
echo "URL: $url\n";
echo "Data: " . json_encode($data) . "\n\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Accept: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$result = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

echo "HTTP Code: $httpCode\n";
echo "Response: $result\n";
