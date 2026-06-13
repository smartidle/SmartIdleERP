<?php
// 直接使用Laravel测试API

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// 测试产品API
$products = App\Models\Product::with('category')->get();
echo "Products from DB:\n";
foreach ($products as $product) {
    echo "  - " . $product->name . " (Category: " . $product->category->name . ")\n";
}
echo "\n";

// 测试员工
$employee = App\Models\Employee::where('email', 'admin@erp.com')->first();
if ($employee) {
    echo "Employee: " . $employee->name . " (" . $employee->email . ")\n";
    echo "Password check: " . (Hash::check('admin123', $employee->password) ? 'OK' : 'FAIL') . "\n";
}
