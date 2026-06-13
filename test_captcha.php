<?php
// Test captcha API

$ch = curl_init('http://localhost:8000/api/v1/captcha');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
$response = curl_exec($ch);
$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$headers = substr($response, 0, $header_size);
$body = substr($response, $header_size);
preg_match('/X-Captcha-Id: ([^\s]+)/', $headers, $matches);
$captchaId = $matches[1] ?? '';
echo "Captcha ID: $captchaId\n";
echo "Image size: " . strlen($body) . " bytes\n";
curl_close($ch);

// Test login with captcha
if ($captchaId) {
    echo "\nTesting login...\n";
    $loginData = [
        'email' => 'admin@erp.com',
        'password' => 'admin123',
        'captcha_id' => $captchaId,
        'captcha' => 'test' // This will fail, captcha code is random
    ];
    
    $ch = curl_init('http://localhost:8000/api/v1/login');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($loginData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    curl_close($ch);
    
    echo "Login response: $result\n";
}
