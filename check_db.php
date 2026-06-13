<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Warehouses: " . App\Models\Warehouse::count() . PHP_EOL;
echo "Purchase Orders: " . App\Models\PurchaseOrder::count() . PHP_EOL;

$po = App\Models\PurchaseOrder::latest()->first();
if ($po) {
    echo "Latest PO ID: " . $po->id . PHP_EOL;
    echo "Latest PO status: " . $po->status . PHP_EOL;
}
