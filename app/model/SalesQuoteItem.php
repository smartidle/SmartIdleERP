<?php

namespace app\model;

use think\Model;

/**
 * 销售报价明细模型
 */
class SalesQuoteItem extends Model
{
    // 表名
    protected $name = 'sales_quote_item';

    // 自动时间戳
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';

    // 类型转换
    protected $type = [
        'quote_id' => 'integer',
        'product_id' => 'integer',
        'sku_id' => 'integer',
        'quantity' => 'float',
        'unit_price' => 'float',
        'discount_rate' => 'float',
        'subtotal' => 'float',
    ];

    /**
     * 规格获取器
     */
    public function getSpecAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * 规格设置器
     */
    public function setSpecAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    /**
     * 报价单关联
     */
    public function quote()
    {
        return $this->belongsTo(SalesQuote::class, 'quote_id');
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
     * 计算小计
     */
    public function calculateSubtotal(): float
    {
        $amount = $this->quantity * $this->unit_price;
        if ($this->discount_rate > 0) {
            $amount = $amount * (1 - $this->discount_rate / 100);
        }
        return round($amount, 2);
    }
}
