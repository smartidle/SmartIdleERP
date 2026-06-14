<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CostRecord extends Model
{
    public $timestamps = false;
    protected $table = 'cost_records';

    protected $fillable = [
        'product_id', 'sku_id', 'cost_type', 'amount',
        'quantity', 'unit_cost', 'reference_type', 'reference_id', 'create_time',
    ];

    protected $casts = ['create_time' => 'datetime'];

    const TYPE_PURCHASE = 1;
    const TYPE_PRODUCTION = 2;
    const TYPE_OTHER = 3;

    public function product() { return $this->belongsTo(Product::class); }
}
