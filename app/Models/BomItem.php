<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BomItem extends Model
{

    protected $table = 'bom_items';

    protected $fillable = [
        'bom_id', 'product_id', 'sku_id', 'quantity',
        'unit', 'loss_rate', 'actual_quantity', 'remark',
    ];

    public function bom()
    {
        return $this->belongsTo(Bom::class, 'bom_id');
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
