<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\Product;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $this->createSalesOrders();
        $this->createPurchaseOrders();
    }

    private function createSalesOrders()
    {
        $customers = Customer::all();
        $products = Product::with('skus')->get();
        
        $statuses = [1, 2, 2, 2, 3, 4, 5, 5, 5, 5];
        
        // 获取当前最大订单号
        $maxOrder = SalesOrder::max('id') ?? 0;
        
        for ($i = 1; $i <= 50; $i++) {
            $customer = $customers->random();
            $orderDate = now()->subDays(rand(0, 60));
            $status = $statuses[array_rand($statuses)];
            $orderNo = 'SO' . date('Ymd') . str_pad($maxOrder + $i, 4, '0', STR_PAD_LEFT);
            
            $order = SalesOrder::create([
                'order_no' => $orderNo,
                'customer_id' => $customer->id,
                'employee_id' => 1,
                'order_date' => $orderDate->format('Y-m-d'),
                'status' => $status,
                'notes' => 'Sample order #' . $i,
            ]);
            
            $totalAmount = 0;
            $productCount = rand(2, 6);
            $selectedProducts = $products->random(min($productCount, $products->count()));
            
            foreach ($selectedProducts as $product) {
                $sku = $product->skus->random();
                $quantity = rand(1, 20);
                $price = $sku->sale_price ?? $product->base_sale_price;
                $amount = $price * $quantity;
                
                SalesOrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'sku_id' => $sku->id,
                    'sku_code' => $sku->sku_code,
                    'product_name' => $product->name,
                    'spec' => $sku->spec_combination,
                    'quantity' => $quantity,
                    'unit_price' => $price,
                    'subtotal' => $amount,
                ]);
                
                $totalAmount += $amount;
            }
            
            $order->update([
                'subtotal' => $totalAmount,
                'tax_amount' => $totalAmount * 0.1,
                'total_amount' => $totalAmount * 1.1,
            ]);
        }
    }

    private function createPurchaseOrders()
    {
        $suppliers = Supplier::all();
        $products = Product::with('skus')->get();
        
        $statuses = [1, 2, 2, 2, 3, 4, 4, 4, 4];
        
        $maxOrder = PurchaseOrder::max('id') ?? 0;
        
        for ($i = 1; $i <= 30; $i++) {
            $supplier = $suppliers->random();
            $orderDate = now()->subDays(rand(0, 60));
            $status = $statuses[array_rand($statuses)];
            $orderNo = 'PO' . date('Ymd') . str_pad($maxOrder + $i, 4, '0', STR_PAD_LEFT);
            
            $order = PurchaseOrder::create([
                'order_no' => $orderNo,
                'supplier_id' => $supplier->id,
                'employee_id' => 1,
                'order_date' => $orderDate->format('Y-m-d'),
                'expected_date' => $orderDate->addDays(rand(7, 30))->format('Y-m-d'),
                'status' => $status,
            ]);
            
            $totalAmount = 0;
            $productCount = rand(2, 5);
            $selectedProducts = $products->random(min($productCount, $products->count()));
            
            foreach ($selectedProducts as $product) {
                $sku = $product->skus->random();
                $quantity = rand(10, 100);
                $price = $sku->cost_price ?? $product->base_cost_price;
                $amount = $price * $quantity;
                
                PurchaseOrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'sku_id' => $sku->id,
                    'product_name' => $product->name,
                    'quantity' => $quantity,
                    'received_qty' => $status == 3 || $status == 4 ? $quantity : 0,
                    'unit_price' => $price,
                    'subtotal' => $amount,
                ]);
                
                $totalAmount += $amount;
            }
            
            $order->update(['total_amount' => $totalAmount]);
        }
    }
}
