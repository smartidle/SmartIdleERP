<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== SmartIdle ERP Test Data ===\n";
echo "Products: " . App\Models\Product::count() . "\n";
echo "Customers: " . App\Models\Customer::count() . "\n";
echo "Suppliers: " . App\Models\Supplier::count() . "\n";
echo "Sales Orders: " . App\Models\SalesOrder::count() . "\n";
echo "Purchase Orders: " . App\Models\PurchaseOrder::count() . "\n";
echo "Warehouses: " . App\Models\Warehouse::count() . "\n";
