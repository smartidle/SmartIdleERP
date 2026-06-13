<?php

namespace app\model;

use think\Model;

/**
 * 销售退货明细模型
 */
class SalesReturnItem extends Model
{
    // 表名
    protected $name = 'sales_return_item';

    // 自动时间戳
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';

    // 类型转换
    protected $type = [
        'return_id' => 'integer',
        'order_item_id' => 'integer',
        'product_id' => 'integer',
        'sku_id' => 'integer',
        'order_qty' => 'float',
        'return_qty' => 'float',
        'return_price' => 'float',
        'return_amount' => 'float',
        'is_replacement' => 'integer',
    ];

    /**
     * 退货单关联
     */
    public function return()
    {
        return $this->belongsTo(SalesReturn::class, 'return_id');
    }

    /**
     * 原订单明细关联
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
