<?php

namespace app\model;

use think\Model;

/**
 * 库存流水模型
 */
class InventoryLog extends Model
{
    // 表名
    protected $name = 'inventory_log';

    // 自动时间戳
    protected $autoWriteTimestamp = false;
    protected $createTime = 'create_time';

    // 类型转换
    protected $type = [
        'product_id' => 'integer',
        'sku_id' => 'integer',
        'warehouse_id' => 'integer',
        'location_id' => 'integer',
        'type' => 'integer',
        'quantity_before' => 'float',
        'quantity_change' => 'float',
        'quantity_after' => 'float',
        'cost_price' => 'float',
        'original_cost' => 'float',
        'reference_id' => 'integer',
        'return_order_id' => 'integer',
        'employee_id' => 'integer',
    ];

    // 禁止自动写入时间戳
    public $autoWriteTimestamp = false;

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
     * 操作人
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    /**
     * 获取类型名称
     */
    public function getTypeName(): string
    {
        $types = [
            1 => '采购入库',
            2 => '销售出库',
            3 => '调拨入',
            4 => '调拨出',
            5 => '盘点盈',
            6 => '盘点亏',
            7 => '生产领料',
            8 => '生产入库',
            9 => '退货入库',
            10 => '退货出库',
            11 => '冻结',
            12 => '解冻',
            99 => '其他',
        ];

        return $types[$this->type] ?? '未知';
    }

    /**
     * 搜索器：SKU筛选
     */
    public function searchSkuIdAttr($query, $value)
    {
        if (!empty($value)) {
            $query->where('sku_id', $value);
        }
    }

    /**
     * 搜索器：仓库筛选
     */
    public function searchWarehouseIdAttr($query, $value)
    {
        if (!empty($value)) {
            $query->where('warehouse_id', $value);
        }
    }

    /**
     * 搜索器：类型筛选
     */
    public function searchTypeAttr($query, $value)
    {
        if ($value !== '') {
            $query->where('type', $value);
        }
    }

    /**
     * 搜索器：时间范围
     */
    public function searchTimeRangeAttr($query, $value)
    {
        if (!empty($value)) {
            if (isset($value[0]) && isset($value[1])) {
                $query->whereBetweenTime('create_time', $value[0], $value[1]);
            }
        }
    }
}
