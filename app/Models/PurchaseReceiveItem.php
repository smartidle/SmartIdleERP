<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseReceiveItem extends Model
{
    use HasFactory;

    protected $table = 'purchase_receive_items';

    protected $fillable = [
        'receive_id',
        'order_item_id',
        'product_id',
        'sku_id',
        'batch_no',
        'quantity',
        'qualified_qty',
        'defective_qty',
        'unit_price',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'qualified_qty' => 'decimal:2',
        'defective_qty' => 'decimal:2',
        'unit_price' => 'decimal:2',
    ];

    // 关联收货单
    public function receive()
    {
        return $this->belongsTo(PurchaseReceive::class);
    }

    // 关联订单明细
    public function orderItem()
    {
        return $this->belongsTo(PurchaseOrderItem::class, 'order_item_id');
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
