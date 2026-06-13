<?php
// Get fresh token
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
echo "Token: " . substr($token, 0, 20) . "...\n";

// Test sales orders
$ch = curl_init('http://localhost:8000/api/v1/sales-orders');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Authorization: Bearer ' . $token
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$orders = json_decode($response, true);
echo "Sales Orders count: " . (is_array($orders['data'] ?? null) ? count($orders['data']) : 'N/A') . "\n";
echo "Full data check: " . json_encode(array_keys($orders)) . "\n";
if (isset($orders['data']['data'])) {
    echo "Nested data count: " . count($orders['data']['data']) . "\n";
}
curl_close($ch);
