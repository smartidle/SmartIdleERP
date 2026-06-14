<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bom extends Model
{

    protected $table = 'boms';

    protected $fillable = [
        'code', 'product_id', 'sku_id', 'version', 'quantity',
        'unit_cost', 'status', 'effective_date', 'invalid_date', 'remark',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'invalid_date' => 'date',
    ];

    // 状态常量
    const STATUS_DRAFT = 0;
    const STATUS_ACTIVE = 1;
    const STATUS_EXPIRED = 2;

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function sku()
    {
        return $this->belongsTo(ProductSku::class, 'sku_id');
    }

    public function items()
    {
        return $this->hasMany(BomItem::class, 'bom_id');
    }

    public function workOrders()
    {
        return $this->hasMany(WorkOrder::class, 'bom_id');
    }
}
