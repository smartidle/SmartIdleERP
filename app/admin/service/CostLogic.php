<?php

namespace app\admin\service;

use app\model\InventoryLog;
use think\facade\Db;

/**
 * 成本计算逻辑
 */
class CostLogic
{
    /**
     * 计算移动加权平均成本
     *
     * @param float $oldCost 原成本
     * @param float $oldQty 原数量
     * @param float $newCost 新入库成本
     * @param float $newQty 新入库数量
     * @return float
     */
    public static function weightedAverage(float $oldCost, float $oldQty, float $newCost, float $newQty): float
    {
        if ($oldQty + $newQty <= 0) {
            return 0;
        }
        return round(($oldCost * $oldQty + $newCost * $newQty) / ($oldQty + $newQty), 4);
    }

    /**
     * 获取SKU的成本价（按移动加权平均）
     *
     * @param int $skuId SKU ID
     * @param int|null $warehouseId 仓库ID
     * @return float
     */
    public static function getSkuCostPrice(int $skuId, ?int $warehouseId = null): float
    {
        $query = Db::name('inventory')
            ->where('sku_id', $skuId)
            ->where('quantity', '>', 0);

        if ($warehouseId !== null) {
            $query->where('warehouse_id', $warehouseId);
        }

        $inventory = $query->find();

        return $inventory ? (float) $inventory['cost_price'] : 0;
    }

    /**
     * 获取销售出库时的成本价
     * 从库存流水中查找最近一次采购入库记录
     *
     * @param int $skuId SKU ID
     * @param int $warehouseId 仓库ID
     * @return float
     */
    public static function getSalesOutCostPrice(int $skuId, int $warehouseId): float
    {
        // 查找最近一次采购入库的成本价
        $log = InventoryLog::where('sku_id', $skuId)
            ->where('warehouse_id', $warehouseId)
            ->where('type', 1) // 采购入库
            ->where('quantity_change', '>', 0)
            ->order('create_time', 'desc')
            ->find();

        if ($log) {
            return (float) $log['cost_price'];
        }

        // 如果没有采购记录，查找其他入库记录
        $log = InventoryLog::where('sku_id', $skuId)
            ->where('warehouse_id', $warehouseId)
            ->where('quantity_change', '>', 0)
            ->order('create_time', 'desc')
            ->find();

        return $log ? (float) $log['cost_price'] : 0;
    }

    /**
     * 获取退货入库时的成本价
     * 策略：使用原销售出库时的成本价
     *
     * @param int $skuId SKU ID
     * @param int $originalSalesOrderId 原销售订单ID
     * @param int $warehouseId 仓库ID
     * @return float
     */
    public static function getReturnInCostPrice(int $skuId, int $originalSalesOrderId, int $warehouseId): float
    {
        // 查找原销售出库的库存流水记录
        $log = InventoryLog::where('sku_id', $skuId)
            ->where('warehouse_id', $warehouseId)
            ->where('reference_type', 'sales_delivery')
            ->where('reference_id', '>', 0)
            ->where('type', 2) // 销售出库
            ->order('create_time', 'desc')
            ->find();

        if ($log) {
            return (float) $log['cost_price'];
        }

        // 如果找不到，使用当前库存成本价
        return self::getSkuCostPrice($skuId, $warehouseId);
    }

    /**
     * 计算订单成本
     *
     * @param int $orderId 订单ID
     * @return array
     */
    public static function calculateOrderCost(int $orderId): array
    {
        $items = Db::name('sales_order_item')
            ->where('order_id', $orderId)
            ->select();

        $totalCost = 0;
        $details = [];

        foreach ($items as $item) {
            $costPrice = self::getSalesOutCostPrice(
                $item['sku_id'],
                Db::name('sales_order')->where('id', $orderId)->value('warehouse_id')
            );

            $itemCost = $costPrice * $item['quantity'];
            $totalCost += $itemCost;

            $details[] = [
                'item_id' => $item['id'],
                'sku_id' => $item['sku_id'],
                'quantity' => $item['quantity'],
                'cost_price' => $costPrice,
                'item_cost' => $itemCost,
            ];
        }

        return [
            'order_id' => $orderId,
            'total_cost' => round($totalCost, 2),
            'details' => $details,
        ];
    }

    /**
     * 计算订单利润
     *
     * @param int $orderId 订单ID
     * @return array
     */
    public static function calculateOrderProfit(int $orderId): array
    {
        $order = Db::name('sales_order')->find($orderId);
        if (!$order) {
            throw new \Exception('订单不存在');
        }

        $costInfo = self::calculateOrderCost($orderId);
        $totalCost = $costInfo['total_cost'];
        $revenue = $order['total_amount'];
        $profit = $revenue - $totalCost;
        $profitRate = $revenue > 0 ? round($profit / $revenue * 100, 2) : 0;

        return [
            'order_id' => $orderId,
            'revenue' => $revenue,
            'cost' => $totalCost,
            'profit' => round($profit, 2),
            'profit_rate' => $profitRate,
        ];
    }
}
