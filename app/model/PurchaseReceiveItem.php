<?php

namespace app\model;

use think\Model;

/**
 * 采购收货明细模型
 */
class PurchaseReceiveItem extends Model
{
    // 表名
    protected $name = 'purchase_receive_item';

    // 自动时间戳
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';

    // 类型转换
    protected $type = [
        'receive_id' => 'integer',
        'order_item_id' => 'integer',
        'product_id' => 'integer',
        'sku_id' => 'integer',
        'order_qty' => 'float',
        'receive_qty' => 'float',
        'qualified_qty' => 'float',
        'defective_qty' => 'float',
        'unit_price' => 'float',
    ];

    /**
     * 收货单关联
     */
    public function receive()
    {
        return $this->belongsTo(PurchaseReceive::class, 'receive_id');
    }

    /**
     * 采购订单明细关联
     */
    public function orderItem()
    {
        return $this->belongsTo(PurchaseOrderItem::class, 'order_item_id');
    }

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
}
