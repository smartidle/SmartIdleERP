<?php

namespace app\model;

/**
 * 供应商报价模型
 */
class PurchaseInquiryQuote extends \think\Model
{
    // 表名
    protected $name = 'purchase_inquiry_quote';

    // 自动时间戳
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';

    // 类型转换
    protected $type = [
        'inquiry_id' => 'integer',
        'inquiry_item_id' => 'integer',
        'supplier_id' => 'integer',
        'product_id' => 'integer',
        'sku_id' => 'integer',
        'unit_price' => 'float',
        'delivery_days' => 'integer',
        'is_selected' => 'integer',
    ];

    /**
     * 询价单关联
     */
    public function inquiry()
    {
        return $this->belongsTo(PurchaseInquiry::class, 'inquiry_id');
    }

    /**
     * 询价明细关联
     */
    public function inquiryItem()
    {
        return $this->belongsTo(PurchaseInquiryItem::class, 'inquiry_item_id');
    }

    /**
     * 供应商关联
     */
    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
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
