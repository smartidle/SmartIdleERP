<?php
/**
 * SmartIdle ERP Comprehensive Test Suite
 */

echo "========================================\n";
echo "SmartIdle ERP Comprehensive Test Suite\n";
echo "========================================\n\n";

$baseUrl = 'http://localhost:8000/api/v1';
$token = null;
$testResults = ['passed' => 0, 'failed' => 0, 'errors' => []];

function apiRequest($url, $method = 'GET', $data = null, $token = null) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    if ($method === 'POST' || $method === 'PUT' || $method === 'DELETE') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        if ($data !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    }
    
    $headers = ['Content-Type: application/json', 'Accept: application/json'];
    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'status' => $httpCode,
        'body' => json_decode($response, true) ?: $response
    ];
}

function test($name, $condition, $error = null) {
    global $testResults;
    
    if ($condition) {
        echo "✅ PASS: $name\n";
        $testResults['passed']++;
    } else {
        echo "❌ FAIL: $name";
        if ($error) echo " - $error";
        echo "\n";
        $testResults['failed']++;
        $testResults['errors'][] = "$name: $error";
    }
}

echo "\n[TEST 1] Login\n";
echo "------------------------------\n";

$res = apiRequest("$baseUrl/login", 'POST', [
    'email' => 'admin@erp.com',
    'password' => 'admin123'
]);

if ($res['body']['success'] ?? false) {
    $token = $res['body']['data']['access_token'] ?? null;
    test("Login successful", !empty($token));
    test("User info returned", isset($res['body']['data']['employee']));
} else {
    echo "❌ Login failed: " . ($res['body']['message'] ?? 'Unknown') . "\n";
}

// Test invalid login
$res = apiRequest("$baseUrl/login", 'POST', [
    'email' => 'wrong@test.com',
    'password' => 'wrongpass'
]);
test("Invalid login fails", !($res['body']['success'] ?? false));

// ========== Products ==========
echo "\n[TEST 2] Products\n";
echo "------------------------------\n";

$res = apiRequest("$baseUrl/products", 'GET', null, $token);
test("Products list accessible", isset($res['body']['success']));

$productId = null;
if ($res['body']['success'] ?? false) {
    $data = $res['body']['data'] ?? [];
    if (is_array($data) && count($data) > 0) {
        $productId = $data[0]['id'] ?? null;
    }
    echo "   Found " . (is_array($data) ? count($data) : 0) . " products\n";
}

// Create product
$res = apiRequest("$baseUrl/products", 'POST', [
    'name' => 'Test Product ' . time(),
    'sku_prefix' => 'TEST' . substr(time(), -4),
    'base_cost_price' => 100,
    'base_sale_price' => 150
], $token);
test("Create product works", $res['body']['success'] ?? false, $res['body']['message'] ?? '');

if ($res['body']['success'] ?? false) {
    $newId = $res['body']['data']['id'] ?? null;
    if ($newId) $productId = $newId;
    test("Product ID returned", $newId !== null);
}

// Update product
if ($productId) {
    $res = apiRequest("$baseUrl/products/$productId", 'PUT', [
        'name' => 'Updated Product'
    ], $token);
    test("Update product works", $res['body']['success'] ?? false, $res['body']['message'] ?? '');
}

// Get single product
if ($productId) {
    $res = apiRequest("$baseUrl/products/$productId", 'GET', null, $token);
    test("Get product works", $res['body']['success'] ?? false, $res['body']['message'] ?? '');
}

// ========== Customers ==========
echo "\n[TEST 3] Customers\n";
echo "------------------------------\n";

$res = apiRequest("$baseUrl/customers", 'GET', null, $token);
test("Customers list accessible", isset($res['body']['success']));

$customerId = null;
if ($res['body']['success'] ?? false) {
    $data = $res['body']['data'] ?? [];
    if (is_array($data) && count($data) > 0) {
        $customerId = $data[0]['id'] ?? null;
    }
}

$res = apiRequest("$baseUrl/customers", 'POST', [
    'name' => 'Test Customer',
    'phone' => '1234567890'
], $token);
test("Create customer works", $res['body']['success'] ?? false, $res['body']['message'] ?? '');

if ($res['body']['success'] ?? false) {
    $newId = $res['body']['data']['id'] ?? null;
    if ($newId) $customerId = $newId;
}

// ========== Suppliers ==========
echo "\n[TEST 4] Suppliers\n";
echo "------------------------------\n";

$res = apiRequest("$baseUrl/suppliers", 'GET', null, $token);
test("Suppliers list accessible", isset($res['body']['success']));

$supplierId = null;
if ($res['body']['success'] ?? false) {
    $data = $res['body']['data'] ?? [];
    if (is_array($data) && count($data) > 0) {
        $supplierId = $data[0]['id'] ?? null;
    }
}

$res = apiRequest("$baseUrl/suppliers", 'POST', [
    'name' => 'Test Supplier',
    'phone' => '9876543210'
], $token);
test("Create supplier works", $res['body']['success'] ?? false, $res['body']['message'] ?? '');

if ($res['body']['success'] ?? false) {
    $newId = $res['body']['data']['id'] ?? null;
    if ($newId) $supplierId = $newId;
}

// ========== Sales Orders ==========
echo "\n[TEST 5] Sales Orders\n";
echo "------------------------------\n";

$res = apiRequest("$baseUrl/sales-orders", 'GET', null, $token);
test("Sales orders list accessible", isset($res['body']['success']));

if ($productId && $customerId) {
    $res = apiRequest("$baseUrl/sales-orders", 'POST', [
        'customer_id' => $customerId,
        'items' => [
            ['product_id' => $productId, 'quantity' => 2, 'price' => 150]
        ]
    ], $token);
    test("Create sales order works", $res['body']['success'] ?? false, $res['body']['message'] ?? '');
    
    if ($res['body']['success'] ?? false) {
        $orderId = $res['body']['data']['id'] ?? null;
        if ($orderId) {
            $res = apiRequest("$baseUrl/sales-orders/$orderId/approve", 'POST', [], $token);
            test("Approve order works", $res['body']['success'] ?? false, $res['body']['message'] ?? '');
        }
    }
} else {
    echo "   ⚠️ Skipped (need product_id=$productId, customer_id=$customerId)\n";
}

// ========== Purchase Orders ==========
echo "\n[TEST 6] Purchase Orders\n";
echo "------------------------------\n";

$res = apiRequest("$baseUrl/purchase-orders", 'GET', null, $token);
test("Purchase orders list accessible", isset($res['body']['success']));

if ($productId && $supplierId) {
    $res = apiRequest("$baseUrl/purchase-orders", 'POST', [
        'supplier_id' => $supplierId,
        'items' => [
            ['product_id' => $productId, 'quantity' => 10, 'price' => 80]
        ]
    ], $token);
    test("Create purchase order works", $res['body']['success'] ?? false, $res['body']['message'] ?? '');
    
    if ($res['body']['success'] ?? false) {
        $poId = $res['body']['data']['id'] ?? null;
        if ($poId) {
            $res = apiRequest("$baseUrl/purchase-orders/$poId/approve", 'POST', [], $token);
            test("Approve purchase order works", $res['body']['success'] ?? false, $res['body']['message'] ?? '');
        }
    }
} else {
    echo "   ⚠️ Skipped (need product_id=$productId, supplier_id=$supplierId)\n";
}

// ========== Inventory ==========
echo "\n[TEST 7] Inventory\n";
echo "------------------------------\n";

$res = apiRequest("$baseUrl/inventory", 'GET', null, $token);
test("Inventory list accessible", isset($res['body']['success']));

// Get a SKU ID for adjustment test
$skuId = null;
if ($res['body']['success'] ?? false) {
    $products = $res['body']['data']['data'] ?? $res['body']['data'] ?? [];
    if (is_array($products) && count($products) > 0) {
        $skus = $products[0]['skus'] ?? [];
        if (is_array($skus) && count($skus) > 0) {
            $skuId = $skus[0]['id'] ?? null;
        }
    }
}

if ($skuId) {
    $res = apiRequest("$baseUrl/inventory/adjust", 'POST', [
        'sku_id' => $skuId,
        'new_stock' => 100,
        'reason' => 'Test adjustment'
    ], $token);
    test("Inventory adjust works", $res['body']['success'] ?? false, $res['body']['message'] ?? '');
} else {
    echo "   ⚠️ Skipped (no SKU found)\n";
}

// ========== Dashboard ==========
echo "\n[TEST 8] Dashboard\n";
echo "------------------------------\n";

$res = apiRequest("$baseUrl/dashboard", 'GET', null, $token);
test("Dashboard accessible", isset($res['body']['success']));

// ========== System Config ==========
echo "\n[TEST 9] System Config\n";
echo "------------------------------\n";

$res = apiRequest("$baseUrl/system-config", 'GET', null, $token);
test("System config accessible", isset($res['body']['success']));

$res = apiRequest("$baseUrl/system-config/batch", 'POST', [
    'configs' => [
        'system_name' => 'SmartIdle ERP Test',
        'company_name' => 'Test Company'
    ]
], $token);
test("Update config works", $res['body']['success'] ?? false, $res['body']['message'] ?? '');

// ========== User ==========
echo "\n[TEST 10] User\n";
echo "------------------------------\n";

$res = apiRequest("$baseUrl/user", 'GET', null, $token);
test("User info accessible", isset($res['body']['success']));

if ($res['body']['success'] ?? false) {
    test("User has ID", isset($res['body']['data']['id']));
    test("User has name", isset($res['body']['data']['name']));
    $hasEmail = isset($res['body']['email']) || isset($res['body']['data']['email']);
    test("User has email", $hasEmail);
}

// ========== SUMMARY ==========
echo "\n========================================\n";
echo "TEST SUMMARY\n";
echo "========================================\n";
echo "Passed: " . $testResults['passed'] . "\n";
echo "Failed: " . $testResults['failed'] . "\n";

if ($testResults['failed'] > 0) {
    echo "\nFailed tests:\n";
    foreach ($testResults['errors'] as $error) {
        echo "  - $error\n";
    }
}

echo "\n" . ($testResults['failed'] == 0 ? "🎉 ALL TESTS PASSED!\n" : "⚠️ Some tests failed\n");
