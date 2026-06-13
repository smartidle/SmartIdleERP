<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesReturnItem extends Model
{
    use HasFactory;

    protected $table = 'sales_return_items';

    protected $fillable = [
        'return_id',
        'order_item_id',
        'product_id',
        'sku_id',
        'order_qty',
        'return_qty',
        'return_price',
        'return_amount',
        'is_replacement',
    ];

    protected $casts = [
        'order_qty' => 'decimal:2',
        'return_qty' => 'decimal:2',
        'return_price' => 'decimal:2',
        'return_amount' => 'decimal:2',
    ];

    // 关联退货单
    public function return()
    {
        return $this->belongsTo(SalesReturn::class, 'return_id');
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
