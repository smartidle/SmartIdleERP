<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryCheckItem extends Model
{
    protected $table = 'inventory_check_items';

    protected $fillable = [
        'check_id', 'product_id', 'sku_id',
        'system_qty', 'actual_qty', 'difference',
        'unit_cost', 'difference_amount', 'status',
        'reason', 'remark',
    ];

    protected $casts = [
        'system_qty' => 'decimal:3',
        'actual_qty' => 'decimal:3',
        'difference' => 'decimal:3',
        'unit_cost' => 'decimal:2',
        'difference_amount' => 'decimal:2',
        'status' => 'integer',
    ];

    const STATUS_PENDING = 1;
    const STATUS_ADJUSTED = 2;
    const STATUS_PROFIT = 3;
    const STATUS_LOSS = 4;

    public function check()
    {
        return $this->belongsTo(InventoryCheck::class, 'check_id');
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