<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseReturnItem extends Model
{
    protected $table = 'purchase_return_items';

    protected $fillable = [
        'return_id', 'receive_item_id', 'product_id', 'sku_id',
        'quantity', 'qualified_qty', 'defective_qty',
        'unit_price', 'amount', 'defect_reason', 'remark',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'qualified_qty' => 'decimal:3',
        'defective_qty' => 'decimal:3',
        'unit_price' => 'decimal:2',
        'amount' => 'decimal:2',
    ];

    public function returnRecord()
    {
        return $this->belongsTo(PurchaseReturn::class, 'return_id');
    }

    public function receiveItem()
    {
        return $this->belongsTo(PurchaseReceiveItem::class, 'receive_item_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function sku()
    {
        return $this->belongsTo(ProductSku::class, 'sku_id');
    }
}