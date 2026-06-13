<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesQuoteItem extends Model
{
    use HasFactory;

    protected $table = 'sales_quote_items';

    protected $fillable = [
        'quote_id',
        'product_id',
        'sku_id',
        'product_name',
        'sku_code',
        'spec',
        'quantity',
        'unit',
        'unit_price',
        'discount_rate',
        'subtotal',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'discount_rate' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'spec' => 'array',
    ];

    // 关联报价单
    public function quote()
    {
        return $this->belongsTo(SalesQuote::class, 'quote_id');
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
