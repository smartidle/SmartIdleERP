<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\InventoryLog;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    /**
     * 锁定库存
     */
    public function lockStock($skuId, $warehouseId, $quantity, $reason = '', $referenceType = '', $referenceId = 0)
    {
        $inventory = Inventory::where('sku_id', $skuId)
            ->where('warehouse_id', $warehouseId)
            ->first();

        if (!$inventory) {
            throw new \Exception('库存记录不存在');
        }

        if ($inventory->available_quantity < $quantity) {
            throw new \Exception('可用库存不足');
        }

        $inventory->locked_quantity += $quantity;
        $inventory->save();

        // 记录日志
        InventoryLog::create([
            'sku_id' => $skuId,
            'warehouse_id' => $warehouseId,
            'type' => InventoryLog::TYPE_FREEZE,
            'quantity_before' => $inventory->quantity,
            'quantity_change' => $quantity,
            'quantity_after' => $inventory->quantity,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'notes' => $reason,
        ]);

        return $inventory;
    }

    /**
     * 解锁库存
     */
    public function unlockStock($skuId, $warehouseId, $quantity, $reason = '', $referenceType = '', $referenceId = 0)
    {
        $inventory = Inventory::where('sku_id', $skuId)
            ->where('warehouse_id', $warehouseId)
            ->first();

        if (!$inventory) {
            throw new \Exception('库存记录不存在');
        }

        $inventory->locked_quantity = max(0, $inventory->locked_quantity - $quantity);
        $inventory->save();

        // 记录日志
        InventoryLog::create([
            'sku_id' => $skuId,
            'warehouse_id' => $warehouseId,
            'type' => InventoryLog::TYPE_UNFREEZE,
            'quantity_before' => $inventory->quantity,
            'quantity_change' => $quantity,
            'quantity_after' => $inventory->quantity,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'notes' => $reason,
        ]);

        return $inventory;
    }

    /**
     * 扣减库存（销售出库等）
     */
    public function deductStock($skuId, $warehouseId, $quantity, $costPrice, $referenceType, $referenceId, $employeeId)
    {
        $inventory = Inventory::where('sku_id', $skuId)
            ->where('warehouse_id', $warehouseId)
            ->first();

        if (!$inventory) {
            throw new \Exception('库存记录不存在');
        }

        if ($inventory->quantity < $quantity) {
            throw new \Exception('库存不足，当前库存: ' . $inventory->quantity);
        }

        $quantityBefore = $inventory->quantity;
        $inventory->quantity -= $quantity;
        $inventory->save();

        // 记录日志
        InventoryLog::create([
            'sku_id' => $skuId,
            'warehouse_id' => $warehouseId,
            'type' => InventoryLog::TYPE_SALES_OUT,
            'quantity_before' => $quantityBefore,
            'quantity_change' => -$quantity,
            'quantity_after' => $inventory->quantity,
            'cost_price' => $costPrice,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'employee_id' => $employeeId,
        ]);

        return $inventory;
    }

    /**
     * 增加库存（退货入库等）
     */
    public function addStock($skuId, $warehouseId, $quantity, $costPrice, $referenceType, $referenceId, $employeeId, $returnOrderId = 0)
    {
        $inventory = Inventory::firstOrCreate(
            [
                'sku_id' => $skuId,
                'warehouse_id' => $warehouseId,
                'location_id' => null,
                'batch_no' => null,
            ],
            [
                'product_id' => \App\Models\ProductSku::find($skuId)?->product_id ?? 0,
                'quantity' => 0,
                'locked_quantity' => 0,
                'cost_price' => $costPrice,
            ]
        );

        $quantityBefore = $inventory->quantity;
        $inventory->quantity += $quantity;
        $inventory->save();

        // 记录日志
        InventoryLog::create([
            'sku_id' => $skuId,
            'warehouse_id' => $warehouseId,
            'type' => InventoryLog::TYPE_RETURN_IN,
            'quantity_before' => $quantityBefore,
            'quantity_change' => $quantity,
            'quantity_after' => $inventory->quantity,
            'cost_price' => $costPrice,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'return_order_id' => $returnOrderId,
            'employee_id' => $employeeId,
        ]);

        return $inventory;
    }

    /**
     * 获取SKU在所有仓库的总库存
     */
    public function getTotalStock($skuId)
    {
        return Inventory::where('sku_id', $skuId)->sum('quantity');
    }

    /**
     * 获取SKU在某仓库的库存
     */
    public function getStockByWarehouse($skuId, $warehouseId)
    {
        return Inventory::where('sku_id', $skuId)
            ->where('warehouse_id', $warehouseId)
            ->first();
    }
}
