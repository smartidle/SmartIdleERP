<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\InventoryLog;
use App\Models\Product;
use App\Models\ProductSku;
use Illuminate\Support\Facades\DB;

/**
 * 成本计算逻辑类
 * 使用移动加权平均法计算成本
 */
class CostLogic
{
    /**
     * 采购入库时更新成本价（移动加权平均）
     */
    public function addPurchaseStock($productId, $skuId, $warehouseId, $quantity, $unitPrice, $referenceType, $referenceId, $employeeId)
    {
        $sku = $skuId ? ProductSku::find($skuId) : null;
        $product = Product::find($productId);

        // 查找或创建库存记录
        $inventory = Inventory::firstOrCreate(
            [
                'sku_id' => $skuId,
                'warehouse_id' => $warehouseId,
                'location_id' => null,
                'batch_no' => null,
            ],
            [
                'product_id' => $productId,
                'quantity' => 0,
                'locked_quantity' => 0,
                'cost_price' => $unitPrice,
            ]
        );

        $quantityBefore = $inventory->quantity;
        $oldCostPrice = $inventory->cost_price;
        $oldTotalValue = $inventory->quantity * $oldCostPrice;
        $newTotalValue = $quantity * $unitPrice;

        // 计算新的加权平均成本价
        $newQuantity = $inventory->quantity + $quantity;
        $newCostPrice = $newQuantity > 0 ? ($oldTotalValue + $newTotalValue) / $newQuantity : $unitPrice;

        // 更新库存
        $inventory->quantity = $newQuantity;
        $inventory->cost_price = $newCostPrice;
        $inventory->save();

        // 记录库存日志
        InventoryLog::create([
            'product_id' => $productId,
            'sku_id' => $skuId,
            'warehouse_id' => $warehouseId,
            'type' => InventoryLog::TYPE_PURCHASE_IN,
            'quantity_before' => $quantityBefore,
            'quantity_change' => $quantity,
            'quantity_after' => $newQuantity,
            'cost_price' => $newCostPrice,
            'original_cost' => $oldCostPrice,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'employee_id' => $employeeId,
        ]);

        return $inventory;
    }

    /**
     * 销售出库时获取成本价
     * 从库存记录中获取最新的加权平均成本价
     */
    public function getSalesCostPrice($skuId, $warehouseId)
    {
        $inventory = Inventory::where('sku_id', $skuId)
            ->where('warehouse_id', $warehouseId)
            ->first();

        if ($inventory) {
            return $inventory->cost_price;
        }

        // 如果没有库存记录，返回SKU或产品的基础成本价
        $sku = ProductSku::find($skuId);
        if ($sku && $sku->cost_price) {
            return $sku->cost_price;
        }

        $product = Product::find($sku?->product_id ?? 0);
        return $product?->base_cost_price ?? 0;
    }

    /**
     * 退货入库时回滚成本价
     * 使用原销售出库时的成本价
     */
    public function returnStock($skuId, $warehouseId, $quantity, $originalCostPrice, $referenceType, $referenceId, $returnOrderId, $employeeId)
    {
        $inventory = Inventory::firstOrCreate(
            [
                'sku_id' => $skuId,
                'warehouse_id' => $warehouseId,
                'location_id' => null,
                'batch_no' => null,
            ],
            [
                'product_id' => ProductSku::find($skuId)?->product_id ?? 0,
                'quantity' => 0,
                'locked_quantity' => 0,
                'cost_price' => $originalCostPrice,
            ]
        );

        $quantityBefore = $inventory->quantity;
        $inventory->quantity += $quantity;
        $inventory->save();

        // 记录库存日志
        InventoryLog::create([
            'product_id' => $inventory->product_id,
            'sku_id' => $skuId,
            'warehouse_id' => $warehouseId,
            'type' => InventoryLog::TYPE_RETURN_IN,
            'quantity_before' => $quantityBefore,
            'quantity_change' => $quantity,
            'quantity_after' => $inventory->quantity,
            'cost_price' => $originalCostPrice,
            'original_cost' => $originalCostPrice,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'return_order_id' => $returnOrderId,
            'employee_id' => $employeeId,
        ]);

        return $inventory;
    }

    /**
     * 生产入库时计算成本
     * BOM成本 + 加工费
     */
    public function calculateProductionCost($bomId, $quantity)
    {
        $bom = \App\Models\Bom::find($bomId);
        if (!$bom) {
            return 0;
        }

        $totalCost = 0;
        foreach ($bom->items as $item) {
            // 获取原材料成本
            $materialCost = $this->getSalesCostPrice($item->sku_id, $item->sku?->product?->id ?? 0);
            $totalCost += $materialCost * $item->quantity * (1 + $item->loss_rate / 100);
        }

        // 加上BOM的标准单位成本
        $totalCost += $bom->unit_cost;

        return $totalCost * $quantity;
    }

    /**
     * 盘点调整库存
     */
    public function adjustInventory($skuId, $warehouseId, $newQuantity, $reason, $referenceType, $referenceId, $employeeId)
    {
        $inventory = Inventory::where('sku_id', $skuId)
            ->where('warehouse_id', $warehouseId)
            ->first();

        if (!$inventory) {
            throw new \Exception('库存记录不存在');
        }

        $quantityBefore = $inventory->quantity;
        $quantityChange = $newQuantity - $quantityBefore;
        $inventory->quantity = $newQuantity;
        $inventory->save();

        // 记录日志
        InventoryLog::create([
            'product_id' => $inventory->product_id,
            'sku_id' => $skuId,
            'warehouse_id' => $warehouseId,
            'type' => $quantityChange >= 0 ? InventoryLog::TYPE_CHECK_PROFIT : InventoryLog::TYPE_CHECK_LOSS,
            'quantity_before' => $quantityBefore,
            'quantity_change' => abs($quantityChange),
            'quantity_after' => $newQuantity,
            'cost_price' => $inventory->cost_price,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'notes' => $reason,
            'employee_id' => $employeeId,
        ]);

        return $inventory;
    }
}
