<?php

namespace app\model;

/**
 * 退货入库明细模型
 */
class SalesReturnDeliveryItem extends \think\Model
{
    // 表名
    protected $name = 'sales_return_delivery_item';

    // 自动时间戳
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';

    // 类型转换
    protected $type = [
        'delivery_id' => 'integer',
        'return_item_id' => 'integer',
        'product_id' => 'integer',
        'sku_id' => 'integer',
        'return_qty' => 'float',
        'received_qty' => 'float',
        'qualified_qty' => 'float',
        'defective_qty' => 'float',
        'cost_price' => 'float',
    ];

    /**
     * 入库单关联
     */
    public function delivery()
    {
        return $this->belongsTo(SalesReturnDelivery::class, 'delivery_id');
    }

    /**
     * 退货明细关联
     */
    public function returnItem()
    {
        return $this->belongsTo(SalesReturnItem::class, 'return_item_id');
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
