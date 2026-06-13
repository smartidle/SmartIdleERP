<?php

namespace App\Services;

use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\SalesQuote;
use App\Models\Product;
use App\Models\ProductSku;
use App\Models\Customer;
use App\Models\Inventory;
use App\Models\InventoryLog;
use App\Services\PromotionService;
use Illuminate\Support\Facades\DB;

class SalesOrderService
{
    protected $promotionService;

    public function __construct(PromotionService $promotionService)
    {
        $this->promotionService = $promotionService;
    }

    /**
     * 创建销售订单
     */
    public function createOrder($data, $employee)
    {
        $customer = Customer::findOrFail($data['customer_id']);

        DB::beginTransaction();
        try {
            // 生成订单编号
            $orderNo = 'SO' . date('YmdHis') . rand(1000, 9999);

            // 创建订单
            $order = SalesOrder::create([
                'order_no' => $orderNo,
                'customer_id' => $data['customer_id'],
                'source' => $data['source'] ?? SalesOrder::SOURCE_MANUAL,
                'order_date' => $data['order_date'] ?? now()->toDateString(),
                'delivery_date' => $data['delivery_date'] ?? null,
                'warehouse_id' => $data['warehouse_id'] ?? null,
                'status' => SalesOrder::STATUS_DRAFT,
                'employee_id' => $employee->id,
                'shipping_contact' => $data['shipping_contact'] ?? null,
                'shipping_phone' => $data['shipping_phone'] ?? null,
                'shipping_address' => $data['shipping_address'] ?? null,
                'shipping_fee' => $data['shipping_fee'] ?? 0,
                'notes' => $data['notes'] ?? null,
            ]);

            $subtotal = 0;
            $totalTax = 0;

            // 添加订单明细
            foreach ($data['items'] as $item) {
                $product = Product::findOrFail($item['product_id']);
                $sku = isset($item['sku_id']) ? ProductSku::find($item['sku_id']) : null;

                // 获取价格（客户专属价 > SKU价 > 产品基础价）
                $unitPrice = $this->getUnitPrice($customer, $product, $sku, $item['unit_price'] ?? null);

                // 获取成本价
                $costPrice = $sku?->cost_price ?? $product->base_cost_price;

                // 计算折扣
                $discountRate = $item['discount_rate'] ?? 0;
                $itemSubtotal = $item['quantity'] * $unitPrice * (1 - $discountRate / 100);

                // 计算税额
                $taxRate = $item['tax_rate'] ?? 0;
                $taxAmount = $itemSubtotal * $taxRate / 100;

                $order->items()->create([
                    'product_id' => $product->id,
                    'sku_id' => $item['sku_id'] ?? null,
                    'product_name' => $product->name,
                    'sku_code' => $sku?->sku_code,
                    'spec' => $sku?->spec_combination,
                    'quantity' => $item['quantity'],
                    'unit' => $item['unit'] ?? $product->base_unit,
                    'unit_price' => $unitPrice,
                    'cost_price' => $costPrice,
                    'tax_rate' => $taxRate,
                    'discount_rate' => $discountRate,
                    'subtotal' => $itemSubtotal,
                ]);

                $subtotal += $itemSubtotal;
                $totalTax += $taxAmount;

                // 锁定库存
                if ($order->warehouse_id && isset($item['sku_id'])) {
                    $this->lockStock($item['sku_id'], $order->warehouse_id, $item['quantity'], $order->id);
                }
            }

            // 应用折扣
            $discountAmount = $data['discount_amount'] ?? 0;
            $promotionAmount = $data['promotion_amount'] ?? 0;
            $couponAmount = $data['coupon_amount'] ?? 0;

            // 计算订单总额
            $order->subtotal = $subtotal;
            $order->tax_amount = $totalTax;
            $order->discount_amount = $discountAmount;
            $order->promotion_amount = $promotionAmount;
            $order->coupon_amount = $couponAmount;
            $order->total_amount = $subtotal + $totalTax - $discountAmount - $promotionAmount - $couponAmount + ($order->shipping_fee ?? 0);
            $order->save();

            DB::commit();

            return $order;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * 更新销售订单
     */
    public function updateOrder($order, $data)
    {
        DB::beginTransaction();
        try {
            // 如果有SKU变化，需要先释放原来的库存锁定
            foreach ($order->items as $item) {
                if ($order->warehouse_id && $item->sku_id) {
                    $this->unlockStock($item->sku_id, $order->warehouse_id, $item->quantity, $order->id);
                }
            }

            // 更新基本信息
            $order->update([
                'customer_id' => $data['customer_id'] ?? $order->customer_id,
                'delivery_date' => $data['delivery_date'] ?? $order->delivery_date,
                'warehouse_id' => $data['warehouse_id'] ?? $order->warehouse_id,
                'shipping_contact' => $data['shipping_contact'] ?? $order->shipping_contact,
                'shipping_phone' => $data['shipping_phone'] ?? $order->shipping_phone,
                'shipping_address' => $data['shipping_address'] ?? $order->shipping_address,
                'shipping_fee' => $data['shipping_fee'] ?? $order->shipping_fee,
                'notes' => $data['notes'] ?? $order->notes,
            ]);

            // 更新明细
            if (isset($data['items'])) {
                // 删除原有明细
                $order->items()->delete();

                $subtotal = 0;
                $totalTax = 0;
                $customer = $order->customer;

                foreach ($data['items'] as $item) {
                    $product = Product::findOrFail($item['product_id']);
                    $sku = isset($item['sku_id']) ? ProductSku::find($item['sku_id']) : null;

                    $unitPrice = $this->getUnitPrice($customer, $product, $sku, $item['unit_price'] ?? null);
                    $costPrice = $sku?->cost_price ?? $product->base_cost_price;
                    $discountRate = $item['discount_rate'] ?? 0;
                    $itemSubtotal = $item['quantity'] * $unitPrice * (1 - $discountRate / 100);
                    $taxRate = $item['tax_rate'] ?? 0;
                    $taxAmount = $itemSubtotal * $taxRate / 100;

                    $order->items()->create([
                        'product_id' => $product->id,
                        'sku_id' => $item['sku_id'] ?? null,
                        'product_name' => $product->name,
                        'sku_code' => $sku?->sku_code,
                        'spec' => $sku?->spec_combination,
                        'quantity' => $item['quantity'],
                        'unit' => $item['unit'] ?? $product->base_unit,
                        'unit_price' => $unitPrice,
                        'cost_price' => $costPrice,
                        'tax_rate' => $taxRate,
                        'discount_rate' => $discountRate,
                        'subtotal' => $itemSubtotal,
                    ]);

                    $subtotal += $itemSubtotal;
                    $totalTax += $taxAmount;

                    // 锁定库存
                    if ($order->warehouse_id && isset($item['sku_id'])) {
                        $this->lockStock($item['sku_id'], $order->warehouse_id, $item['quantity'], $order->id);
                    }
                }

                $order->subtotal = $subtotal;
                $order->tax_amount = $totalTax;
                $order->total_amount = $subtotal + $totalTax - $order->discount_amount - $order->promotion_amount - $order->coupon_amount + $order->shipping_fee;
                $order->save();
            }

            DB::commit();

            return $order;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * 获取产品单价（优先级：客户专属价 > SKU价 > 产品基础价 > 传入价）
     */
    protected function getUnitPrice($customer, $product, $sku, $inputPrice = null)
    {
        // 客户专属价格
        if ($customer) {
            $customerPrice = $customer->prices()
                ->where('product_id', $product->id)
                ->where(function ($q) use ($sku) {
                    if ($sku) {
                        $q->where('sku_id', $sku->id)->orWhereNull('sku_id');
                    } else {
                        $q->whereNull('sku_id');
                    }
                })
                ->where(function ($q) {
                    $today = now()->toDateString();
                    $q->where(function ($q2) use ($today) {
                        $q2->whereNull('valid_from')->orWhere('valid_from', '<=', $today);
                    })->where(function ($q2) use ($today) {
                        $q2->whereNull('valid_to')->orWhere('valid_to', '>=', $today);
                    });
                })
                ->first();

            if ($customerPrice) {
                return $customerPrice->price;
            }

            // 客户折扣率
            if ($customer->discount_rate > 0) {
                $basePrice = $sku?->sale_price ?? $product->base_sale_price;
                return $basePrice * (1 - $customer->discount_rate / 100);
            }
        }

        // SKU价格
        if ($sku && $sku->sale_price) {
            return $sku->sale_price;
        }

        // 产品基础价格
        if ($product->base_sale_price) {
            return $product->base_sale_price;
        }

        // 传入价格
        if ($inputPrice !== null) {
            return $inputPrice;
        }

        return 0;
    }

    /**
     * 锁定库存
     */
    protected function lockStock($skuId, $warehouseId, $quantity, $orderId)
    {
        $inventory = Inventory::where('sku_id', $skuId)
            ->where('warehouse_id', $warehouseId)
            ->first();

        if ($inventory) {
            $inventory->locked_quantity += $quantity;
            $inventory->save();
        }
    }

    /**
     * 解锁库存
     */
    protected function unlockStock($skuId, $warehouseId, $quantity, $orderId)
    {
        $inventory = Inventory::where('sku_id', $skuId)
            ->where('warehouse_id', $warehouseId)
            ->first();

        if ($inventory) {
            $inventory->locked_quantity = max(0, $inventory->locked_quantity - $quantity);
            $inventory->save();
        }
    }
}
