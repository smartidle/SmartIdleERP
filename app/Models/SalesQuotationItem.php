<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesQuotationItem extends Model
{
    protected $table = 'sales_quotation_items';

    protected $fillable = [
        'quotation_id', 'product_id', 'sku_id',
        'product_name', 'sku_code', 'specs',
        'quantity', 'unit', 'unit_price',
        'cost_price', 'subtotal',
        'discount_rate', 'discount_amount', 'remark',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function quotation()
    {
        return $this->belongsTo(\App\Models\SalesQuotation::class, 'quotation_id');
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