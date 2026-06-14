<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryFreeze extends Model
{
    protected $table = 'inventory_freezes';

    protected $fillable = [
        'product_id', 'sku_id', 'warehouse_id', 'quantity',
        'reason', 'order_id', 'unfreeze_time', 'status',
        'unfreeze_by', 'unfreeze_at',
    ];

    protected $casts = [
        'unfreeze_time' => 'datetime',
        'unfreeze_at' => 'datetime',
    ];

    const STATUS_FROZEN = 1;
    const STATUS_UNFROZEN = 2;

    public function product() { return $this->belongsTo(Product::class); }
    public function sku() { return $this->belongsTo(ProductSku::class, 'sku_id'); }
    public function warehouse() { return $this->belongsTo(Warehouse::class); }
}
