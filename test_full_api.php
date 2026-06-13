<?php
// 完整API测试

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// 登录获取Token
$employee = App\Models\Employee::where('email', 'admin@erp.com')->first();
$token = $employee->createToken('test-token')->plainTextToken;

echo "========================================\n";
echo "SmartIdle ERP API 测试报告\n";
echo "========================================\n\n";

// 测试函数
function testApi($url, $token, $method = 'GET', $data = null) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $headers = [
        'Authorization: Bearer ' . $token,
        'Accept: application/json',
        'Content-Type: application/json'
    ];
    
    if ($method === 'POST' && $data) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $result = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($result, true);
}

// 1. 仪表盘
echo "1. 仪表盘数据\n";
$result = testApi('http://localhost:8000/api/v1/dashboard', $token);
echo "   今日销售额: " . ($result['data']['today_sales'] ?? 'N/A') . "\n";
echo "   待处理订单: " . ($result['data']['pending_orders'] ?? 'N/A') . "\n";
echo "   库存预警: " . ($result['data']['low_stock_warning'] ?? 'N/A') . "\n\n";

// 2. 产品列表
echo "2. 产品列表\n";
$result = testApi('http://localhost:8000/api/v1/products', $token);
$products = $result['data']['data'] ?? [];
echo "   产品总数: " . count($products) . "\n";
if (count($products) > 0) {
    echo "   示例产品: " . ($products[0]['name'] ?? 'N/A') . "\n";
}
echo "   分类: " . ($result['data']['categories'] ?? 'N/A') . " 个\n\n";

// 3. 库存列表
echo "3. 库存列表\n";
$result = testApi('http://localhost:8000/api/v1/inventory', $token);
$inventories = $result['data']['data'] ?? [];
echo "   库存记录数: " . count($inventories) . "\n\n";

// 4. 客户列表
echo "4. 客户列表\n";
$result = testApi('http://localhost:8000/api/v1/customers', $token);
$customers = $result['data']['data'] ?? [];
echo "   客户总数: " . count($customers) . "\n\n";

// 5. 供应商列表
echo "5. 供应商列表\n";
$result = testApi('http://localhost:8000/api/v1/suppliers', $token);
$suppliers = $result['data']['data'] ?? [];
echo "   供应商总数: " . count($suppliers) . "\n\n";

// 6. 销售订单
echo "6. 销售订单\n";
$result = testApi('http://localhost:8000/api/v1/sales-orders', $token);
$orders = $result['data']['data'] ?? [];
echo "   订单总数: " . count($orders) . "\n\n";

// 7. 采购订单
echo "7. 采购订单\n";
$result = testApi('http://localhost:8000/api/v1/purchase-orders', $token);
$purchases = $result['data']['data'] ?? [];
echo "   采购单总数: " . count($purchases) . "\n\n";

// 8. 库存预警
echo "8. 库存预警\n";
$result = testApi('http://localhost:8000/api/v1/inventory/warning', $token);
$warnings = $result['data']['data'] ?? [];
echo "   预警数量: " . count($warnings) . "\n\n";

echo "========================================\n";
echo "测试完成！\n";
echo "========================================\n";
