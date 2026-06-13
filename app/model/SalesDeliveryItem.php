<?php

namespace app\model;

use think\Model;

/**
 * 销售发货明细模型
 */
class SalesDeliveryItem extends Model
{
    // 表名
    protected $name = 'sales_delivery_item';

    // 自动时间戳
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';

    // 类型转换
    protected $type = [
        'delivery_id' => 'integer',
        'order_item_id' => 'integer',
        'product_id' => 'integer',
        'sku_id' => 'integer',
        'quantity' => 'float',
    ];

    /**
     * 发货单关联
     */
    public function delivery()
    {
        return $this->belongsTo(SalesDelivery::class, 'delivery_id');
    }

    /**
     * 订单明细关联
     */
    public function orderItem()
    {
        return $this->belongsTo(SalesOrderItem::class, 'order_item_id');
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
