<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesOrderItem extends Model
{
    use HasFactory;

    protected $table = 'sales_order_items';

    protected $fillable = [
        'order_id',
        'product_id',
        'sku_id',
        'product_name',
        'sku_code',
        'spec',
        'quantity',
        'unit',
        'unit_price',
        'cost_price',
        'tax_rate',
        'discount_rate',
        'subtotal',
        'delivered_qty',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'discount_rate' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'delivered_qty' => 'decimal:2',
        'spec' => 'array',
    ];

    // 关联订单
    public function order()
    {
        return $this->belongsTo(SalesOrder::class, 'order_id');
    }

    // 关联产品
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // 关联SKU
    public function sku()
    {
        return $this->belongsTo(ProductSku::class);
    }

    // 获取未发货数量
    public function getUndeliveredQtyAttribute()
    {
        return max(0, $this->quantity - $this->delivered_qty);
    }

    // 检查是否完全发货
    public function isFullyDelivered()
    {
        return bccomp($this->delivered_qty, $this->quantity, 2) >= 0;
    }
}
