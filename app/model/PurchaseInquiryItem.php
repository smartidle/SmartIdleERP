<?php

namespace app\model;

/**
 * 询价明细模型
 */
class PurchaseInquiryItem extends \think\Model
{
    // 表名
    protected $name = 'purchase_inquiry_item';

    // 自动时间戳
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';

    // 类型转换
    protected $type = [
        'inquiry_id' => 'integer',
        'product_id' => 'integer',
        'sku_id' => 'integer',
        'quantity' => 'float',
    ];

    /**
     * 询价单关联
     */
    public function inquiry()
    {
        return $this->belongsTo(PurchaseInquiry::class, 'inquiry_id');
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
