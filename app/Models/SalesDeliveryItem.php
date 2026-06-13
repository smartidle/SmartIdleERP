<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesDeliveryItem extends Model
{
    use HasFactory;

    protected $table = 'sales_delivery_items';

    protected $fillable = [
        'delivery_id',
        'order_item_id',
        'product_id',
        'sku_id',
        'quantity',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
    ];

    // 关联发货单
    public function delivery()
    {
        return $this->belongsTo(SalesDelivery::class);
    }

    // 关联订单明细
    public function orderItem()
    {
        return $this->belongsTo(SalesOrderItem::class, 'order_item_id');
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
}
