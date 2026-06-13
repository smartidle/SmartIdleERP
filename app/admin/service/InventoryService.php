<?php

namespace app\admin\service;

use app\model\Inventory;
use app\model\InventoryLog;
use app\model\ProductSku;
use app\model\Warehouse;
use think\facade\Db;

/**
 * 库存服务
 */
class InventoryService
{
    /**
     * 扣减库存（销售出库）
     *
     * @param int $skuId SKU ID
     * @param float $quantity 数量
     * @param int $warehouseId 仓库ID
     * @param string $referenceType 关联单据类型
     * @param int $referenceId 关联单据ID
     * @param int $employeeId 操作人
     * @return array 扣减结果
     */
    public function deductStock(
        int $skuId,
        float $quantity,
        int $warehouseId,
        string $referenceType = '',
        int $referenceId = 0,
        int $employeeId = 0
    ): array {
        $sku = ProductSku::find($skuId);
        if (!$sku) {
            throw new \Exception('SKU不存在');
        }

        // 查找库存记录
        $inventory = Inventory::where('sku_id', $skuId)
            ->where('warehouse_id', $warehouseId)
            ->find();

        if (!$inventory) {
            throw new \Exception('库存记录不存在');
        }

        // 检查可用库存
        $availableQty = $inventory->getAvailableQuantity();
        if ($availableQty < $quantity) {
            throw new \Exception('可用库存不足，当前可用：' . $availableQty);
        }

        // 执行扣减
        $beforeQty = $inventory->quantity;
        $inventory->quantity -= $quantity;
        $inventory->save();

        // 记录库存流水
        InventoryLog::create([
            'product_id' => $sku->product_id,
            'sku_id' => $skuId,
            'warehouse_id' => $warehouseId,
            'type' => Inventory::TYPE_SALES_OUT,
            'quantity_before' => $beforeQty,
            'quantity_change' => -$quantity,
            'quantity_after' => $inventory->quantity,
            'cost_price' => $inventory->cost_price,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'employee_id' => $employeeId,
            'create_time' => time(),
        ]);

        return [
            'sku_id' => $skuId,
            'warehouse_id' => $warehouseId,
            'quantity' => $quantity,
            'before_quantity' => $beforeQty,
            'after_quantity' => $inventory->quantity,
            'cost_price' => $inventory->cost_price,
        ];
    }

    /**
     * 增加库存（采购入库、退货入库等）
     *
     * @param int $skuId SKU ID
     * @param float $quantity 数量
     * @param int $warehouseId 仓库ID
     * @param float $costPrice 成本价
     * @param string $batchNo 批次号
     * @param string $referenceType 关联单据类型
     * @param int $referenceId 关联单据ID
     * @param int $employeeId 操作人
     * @return array 入库结果
     */
    public function addStock(
        int $skuId,
        float $quantity,
        int $warehouseId,
        float $costPrice = 0,
        string $batchNo = '',
        string $referenceType = '',
        int $referenceId = 0,
        int $employeeId = 0
    ): array {
        $sku = ProductSku::find($skuId);
        if (!$sku) {
            throw new \Exception('SKU不存在');
        }

        // 如果没有指定成本价，使用SKU的有效成本价
        if ($costPrice <= 0) {
            $costPrice = $sku->getEffectiveCostPrice();
        }

        // 查找或创建库存记录
        $inventory = Inventory::where('sku_id', $skuId)
            ->where('warehouse_id', $warehouseId)
            ->find();

        if ($inventory) {
            // 更新库存
            $beforeQty = $inventory->quantity;
            $beforeCost = (float) $inventory->cost_price;

            $inventory->quantity += $quantity;
            // 移动加权平均计算新成本价
            if ($beforeQty > 0) {
                $inventory->cost_price = weight_avg($beforeCost, $beforeQty, $costPrice, $quantity);
            } else {
                $inventory->cost_price = $costPrice;
            }
            $inventory->save();
        } else {
            // 创建新库存记录
            $beforeQty = 0;
            $inventory = Inventory::create([
                'product_id' => $sku->product_id,
                'sku_id' => $skuId,
                'warehouse_id' => $warehouseId,
                'batch_no' => $batchNo,
                'quantity' => $quantity,
                'locked_quantity' => 0,
                'cost_price' => $costPrice,
            ]);
        }

        // 记录库存流水
        InventoryLog::create([
            'product_id' => $sku->product_id,
            'sku_id' => $skuId,
            'warehouse_id' => $warehouseId,
            'type' => Inventory::TYPE_PURCHASE_IN,
            'quantity_before' => $beforeQty,
            'quantity_change' => $quantity,
            'quantity_after' => $inventory->quantity,
            'cost_price' => $inventory->cost_price,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'employee_id' => $employeeId,
            'create_time' => time(),
        ]);

        return [
            'sku_id' => $skuId,
            'warehouse_id' => $warehouseId,
            'quantity' => $quantity,
            'before_quantity' => $beforeQty,
            'after_quantity' => $inventory->quantity,
            'cost_price' => $inventory->cost_price,
        ];
    }

    /**
     * 锁定库存
     *
     * @param int $skuId SKU ID
     * @param float $quantity 锁定数量
     * @param int $warehouseId 仓库ID
     * @param string $reason 锁定原因
     * @param int $orderId 关联订单ID
     * @return bool
     */
    public function lockStock(
        int $skuId,
        float $quantity,
        int $warehouseId,
        string $reason = '',
        int $orderId = 0
    ): bool {
        $inventory = Inventory::where('sku_id', $skuId)
            ->where('warehouse_id', $warehouseId)
            ->find();

        if (!$inventory) {
            throw new \Exception('库存记录不存在');
        }

        $availableQty = $inventory->getAvailableQuantity();
        if ($availableQty < $quantity) {
            throw new \Exception('可用库存不足，无法锁定');
        }

        $inventory->locked_quantity += $quantity;
        $inventory->save();

        // 记录冻结日志
        InventoryLog::create([
            'product_id' => $inventory->product_id,
            'sku_id' => $skuId,
            'warehouse_id' => $warehouseId,
            'type' => Inventory::TYPE_FREEZE,
            'quantity_before' => $inventory->quantity - $quantity,
            'quantity_change' => 0,
            'quantity_after' => $inventory->quantity,
            'reference_type' => 'order_lock',
            'reference_id' => $orderId,
            'notes' => $reason,
            'create_time' => time(),
        ]);

        return true;
    }

    /**
     * 解锁库存
     *
     * @param int $skuId SKU ID
     * @param float $quantity 解锁数量
     * @param int $warehouseId 仓库ID
     * @return bool
     */
    public function unlockStock(int $skuId, float $quantity, int $warehouseId): bool
    {
        $inventory = Inventory::where('sku_id', $skuId)
            ->where('warehouse_id', $warehouseId)
            ->find();

        if (!$inventory) {
            throw new \Exception('库存记录不存在');
        }

        if ($inventory->locked_quantity < $quantity) {
            throw new \Exception('锁定库存不足');
        }

        $inventory->locked_quantity -= $quantity;
        $inventory->save();

        return true;
    }

    /**
     * 获取SKU库存详情
     *
     * @param int $skuId SKU ID
     * @param int|null $warehouseId 仓库ID（可选）
     * @return array
     */
    public function getSkuStock(int $skuId, ?int $warehouseId = null): array
    {
        $query = Inventory::where('sku_id', $skuId);

        if ($warehouseId !== null) {
            $query->where('warehouse_id', $warehouseId);
        }

        $inventories = $query->select();
        $totalQty = 0;
        $totalLocked = 0;
        $details = [];

        foreach ($inventories as $inv) {
            $totalQty += $inv->quantity;
            $totalLocked += $inv->locked_quantity;
            $details[] = [
                'warehouse_id' => $inv->warehouse_id,
                'warehouse_name' => $inv->warehouse->name ?? '',
                'quantity' => $inv->quantity,
                'locked_quantity' => $inv->locked_quantity,
                'available_quantity' => $inv->getAvailableQuantity(),
                'cost_price' => $inv->cost_price,
                'batch_no' => $inv->batch_no,
            ];
        }

        return [
            'sku_id' => $skuId,
            'total_quantity' => $totalQty,
            'total_locked' => $totalLocked,
            'available_quantity' => $totalQty - $totalLocked,
            'details' => $details,
        ];
    }

    /**
     * 库存预警检查
     *
     * @return array 预警列表
     */
    public function checkStockWarning(): array
    {
        $warnings = [];

        // 检查低库存
        $lowStock = Db::name('inventory i')
            ->join('product p', 'p.id = i.product_id')
            ->join('product_sku ps', 'ps.id = i.sku_id')
            ->field('i.*, p.name as product_name, ps.sku_code')
            ->whereRaw('(i.quantity - i.locked_quantity) < p.min_stock')
            ->where('p.min_stock', '>', 0)
            ->select();

        foreach ($lowStock as $item) {
            $warnings[] = [
                'type' => 'low_stock',
                'sku_id' => $item['sku_id'],
                'product_name' => $item['product_name'],
                'sku_code' => $item['sku_code'],
                'warehouse_id' => $item['warehouse_id'],
                'current_qty' => $item['quantity'] - $item['locked_quantity'],
                'min_stock' => $item['min_stock'],
                'message' => "SKU {$item['sku_code']} 库存不足，当前：{$item['quantity']}，最低：{$item['min_stock']}",
            ];
        }

        // 检查超储
        $overStock = Db::name('inventory i')
            ->join('product p', 'p.id = i.product_id')
            ->join('product_sku ps', 'ps.id = i.sku_id')
            ->field('i.*, p.name as product_name, ps.sku_code')
            ->whereRaw('(i.quantity - i.locked_quantity) > p.max_stock')
            ->where('p.max_stock', '>', 0)
            ->select();

        foreach ($overStock as $item) {
            $warnings[] = [
                'type' => 'over_stock',
                'sku_id' => $item['sku_id'],
                'product_name' => $item['product_name'],
                'sku_code' => $item['sku_code'],
                'warehouse_id' => $item['warehouse_id'],
                'current_qty' => $item['quantity'] - $item['locked_quantity'],
                'max_stock' => $item['max_stock'],
                'message' => "SKU {$item['sku_code']} 库存超储，当前：{$item['quantity']}，最高：{$item['max_stock']}",
            ];
        }

        // 检查效期预警
        $expiryWarning = Db::name('inventory i')
            ->join('product p', 'p.id = i.product_id')
            ->join('product_sku ps', 'ps.id = i.sku_id')
            ->field('i.*, p.name as product_name, ps.sku_code')
            ->where('i.expiry_date', '<=', date('Y-m-d', strtotime('+30 days')))
            ->where('i.expiry_date', '>', date('Y-m-d'))
            ->where('p.shelf_life_days', '>', 0)
            ->select();

        foreach ($expiryWarning as $item) {
            $warnings[] = [
                'type' => 'expiry_warning',
                'sku_id' => $item['sku_id'],
                'product_name' => $item['product_name'],
                'sku_code' => $item['sku_code'],
                'warehouse_id' => $item['warehouse_id'],
                'expiry_date' => $item['expiry_date'],
                'message' => "SKU {$item['sku_code']} 即将过期，到期日期：{$item['expiry_date']}",
            ];
        }

        return $warnings;
    }
}
