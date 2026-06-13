<?php

namespace app\model;

/**
 * 工单领料模型
 */
class WorkOrderMaterial extends \think\Model
{
    // 表名
    protected $name = 'work_order_material';

    // 自动时间戳
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';

    // 类型转换
    protected $type = [
        'work_order_id' => 'integer',
        'product_id' => 'integer',
        'sku_id' => 'integer',
        'warehouse_id' => 'integer',
        'required_qty' => 'float',
        'issued_qty' => 'float',
        'returned_qty' => 'float',
    ];

    /**
     * 工单关联
     */
    public function workOrder()
    {
        return $this->belongsTo(WorkOrder::class, 'work_order_id');
    }

    /**
     * 原材料产品
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * 原材料SKU
     */
    public function sku()
    {
        return $this->belongsTo(ProductSku::class, 'sku_id');
    }

    /**
     * 仓库
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    /**
     * 获取未领料数量
     */
    public function getUnissuedQty(): float
    {
        return $this->required_qty - $this->issued_qty + $this->returned_qty;
    }
}
