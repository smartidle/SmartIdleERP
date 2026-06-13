<?php

namespace app\model;

use think\Model;
use think\facade\Db;

/**
 * 库存模型
 */
class Inventory extends Model
{
    // 表名
    protected $name = 'inventory';

    // 自动时间戳
    protected $autoWriteTimestamp = 'int';
    protected $updateTime = 'update_time';

    // 类型转换
    protected $type = [
        'product_id' => 'integer',
        'sku_id' => 'integer',
        'warehouse_id' => 'integer',
        'location_id' => 'integer',
        'quantity' => 'float',
        'locked_quantity' => 'float',
        'cost_price' => 'float',
    ];

    // 库存类型常量
    const TYPE_PURCHASE_IN = 1;      // 采购入库
    const TYPE_SALES_OUT = 2;        // 销售出库
    const TYPE_TRANSFER_IN = 3;      // 调拨入
    const TYPE_TRANSFER_OUT = 4;     // 调拨出
    const TYPE_CHECK_IN = 5;        // 盘点盈
    const TYPE_CHECK_OUT = 6;        // 盘点亏
    const TYPE_MATERIAL_OUT = 7;     // 生产领料
    const TYPE_MATERIAL_IN = 8;     // 生产入库
    const TYPE_RETURN_IN = 9;        // 退货入库
    const TYPE_RETURN_OUT = 10;      // 退货出库
    const TYPE_FREEZE = 11;          // 冻结
    const TYPE_UNFREEZE = 12;         // 解冻
    const TYPE_OTHER = 99;           // 其他

    /**
     * 产品关联
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * SKU关联
     */
    public function sku()
    {
        return $this->belongsTo(ProductSku::class, 'sku_id');
    }

    /**
     * 仓库关联
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    /**
     * 库位关联
     */
    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

    /**
     * 获取可用数量
     */
    public function getAvailableQuantity(): float
    {
        return $this->quantity - $this->locked_quantity;
    }

    /**
     * 库存变动
     * @param float $quantity 变动数量（正数为入库，负数为出库）
     * @param int $type 变动类型
     * @param string $referenceType 关联单据类型
     * @param int $referenceId 关联单据ID
     * @param int $employeeId 操作人
     * @param string $notes 备注
     */
    public function change(
        float $quantity,
        int $type,
        string $referenceType = '',
        int $referenceId = 0,
        int $employeeId = 0,
        string $notes = ''
    ): void {
        $beforeQuantity = $this->quantity;

        if ($quantity > 0) {
            $this->quantity += $quantity;
        } else {
            if ($this->quantity + $quantity < 0) {
                throw new \Exception('库存不足');
            }
            $this->quantity += $quantity;
        }

        $this->save();

        // 记录库存流水
        $this->createInventoryLog(
            $type,
            $beforeQuantity,
            $quantity,
            $referenceType,
            $referenceId,
            $employeeId,
            $notes
        );
    }

    /**
     * 创建库存流水记录
     */
    protected function createInventoryLog(
        int $type,
        float $beforeQuantity,
        float $quantityChange,
        string $referenceType,
        int $referenceId,
        int $employeeId,
        string $notes
    ): void {
        InventoryLog::create([
            'product_id' => $this->product_id,
            'sku_id' => $this->sku_id,
            'warehouse_id' => $this->warehouse_id,
            'location_id' => $this->location_id,
            'batch_no' => $this->batch_no,
            'type' => $type,
            'quantity_before' => $beforeQuantity,
            'quantity_change' => $quantityChange,
            'quantity_after' => $this->quantity,
            'cost_price' => $this->cost_price,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'employee_id' => $employeeId,
            'notes' => $notes,
            'create_time' => time(),
        ]);
    }

    /**
     * 锁定库存
     */
    public function lock(float $quantity): void
    {
        if ($quantity > $this->getAvailableQuantity()) {
            throw new \Exception('可锁定库存不足');
        }

        $this->locked_quantity += $quantity;
        $this->save();
    }

    /**
     * 解锁库存
     */
    public function unlock(float $quantity): void
    {
        if ($quantity > $this->locked_quantity) {
            throw new \Exception('锁定库存不足');
        }

        $this->locked_quantity -= $quantity;
        $this->save();
    }

    /**
     * 更新成本价（移动加权平均）
     */
    public function updateCostPrice(float $newPrice, float $newQuantity): void
    {
        $this->cost_price = weight_avg(
            (float) $this->cost_price,
            $this->quantity,
            $newPrice,
            $newQuantity
        );
        $this->save();
    }
}
