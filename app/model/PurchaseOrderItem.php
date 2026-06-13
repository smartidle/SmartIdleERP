<?php

namespace app\model;

use think\Model;

/**
 * 采购订单明细模型
 */
class PurchaseOrderItem extends Model
{
    // 表名
    protected $name = 'purchase_order_item';

    // 自动时间戳
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';

    // 类型转换
    protected $type = [
        'order_id' => 'integer',
        'product_id' => 'integer',
        'sku_id' => 'integer',
        'quantity' => 'float',
        'unit_price' => 'float',
        'tax_rate' => 'float',
        'subtotal' => 'float',
        'received_qty' => 'float',
    ];

    /**
     * 订单关联
     */
    public function order()
    {
        return $this->belongsTo(PurchaseOrder::class, 'order_id');
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

    /**
     * 获取未收货数量
     */
    public function getUnreceivedQty(): float
    {
        return $this->quantity - $this->received_qty;
    }

    /**
     * 是否完全收货
     */
    public function isFullyReceived(): bool
    {
        return bccomp($this->received_qty, $this->quantity, 2) >= 0;
    }
}
