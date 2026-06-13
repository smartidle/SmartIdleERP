<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

App\Models\SystemConfig::where('key', 'captcha_enabled')->update(['value' => '0']);
echo "Captcha disabled\n";

// Login
$ch = curl_init('http://localhost:8000/api/v1/login');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'email' => 'admin@erp.com',
    'password' => 'admin123'
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$login = json_decode(curl_exec($ch), true);
$token = $login['data']['access_token'] ?? '';
echo "Token: " . substr($token, 0, 30) . "...\n";
curl_close($ch);

if ($token) {
    // Test sales orders
    $ch = curl_init('http://localhost:8000/api/v1/sales-orders');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Authorization: Bearer ' . $token
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $orders = json_decode($response, true);
    
    echo "API Response keys: " . implode(', ', array_keys($orders)) . "\n";
    
    if (isset($orders['data'])) {
        if (isset($orders['data']['data'])) {
            echo "Sales Orders (nested): " . count($orders['data']['data']) . "\n";
        } else {
            echo "Sales Orders: " . count($orders['data']) . "\n";
        }
    }
    curl_close($ch);
}
