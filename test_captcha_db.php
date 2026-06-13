<?php
// Test captcha with database

$ch = curl_init('http://localhost:8000/api/v1/captcha');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
$response = curl_exec($ch);
$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$headers = substr($response, 0, $header_size);
preg_match('/X-Captcha-Key: ([^\s]+)/', $headers, $matches);
$captchaKey = $matches[1] ?? '';
echo "Captcha Key: $captchaKey\n";

// Get captcha code from database
$pdo = new PDO('sqlite:database/database.sqlite');
$stmt = $pdo->prepare('SELECT code FROM captchas WHERE key = ?');
$stmt->execute([$captchaKey]);
$captcha = $stmt->fetch(PDO::FETCH_ASSOC);
$captchaCode = $captcha['code'] ?? '';
echo "Captcha Code from DB: $captchaCode\n";

// Test login with captcha
$ch = curl_init('http://localhost:8000/api/v1/login');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'email' => 'admin@erp.com',
    'password' => 'admin123',
    'captcha_key' => $captchaKey,
    'captcha_code' => $captchaCode
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$result = curl_exec($ch);
curl_close($ch);
echo "Login Result: $result\n";
