<?php

namespace app\model;

use think\Model;

/**
 * 销售订单明细模型
 */
class SalesOrderItem extends Model
{
    // 表名
    protected $name = 'sales_order_item';

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
        'cost_price' => 'float',
        'tax_rate' => 'float',
        'discount_rate' => 'float',
        'subtotal' => 'float',
        'delivered_qty' => 'float',
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
     * 订单关联
     */
    public function order()
    {
        return $this->belongsTo(SalesOrder::class, 'order_id');
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
     * 获取未发货数量
     */
    public function getUndeliveredQty(): float
    {
        return $this->quantity - $this->delivered_qty;
    }

    /**
     * 是否完全发货
     */
    public function isFullyDelivered(): bool
    {
        return bccomp($this->delivered_qty, $this->quantity, 2) >= 0;
    }

    /**
     * 计算小计（应用折扣后）
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
