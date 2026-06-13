<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrderItem extends Model
{
    use HasFactory;

    protected $table = 'purchase_order_items';

    protected $fillable = [
        'order_id',
        'product_id',
        'sku_id',
        'product_name',
        'quantity',
        'unit_price',
        'tax_rate',
        'subtotal',
        'received_qty',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'received_qty' => 'decimal:2',
    ];

    // 关联订单
    public function order()
    {
        return $this->belongsTo(PurchaseOrder::class, 'order_id');
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

    // 获取未收货数量
    public function getUnreceivedQtyAttribute()
    {
        return max(0, $this->quantity - $this->received_qty);
    }
}
