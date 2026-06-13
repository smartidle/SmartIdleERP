<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplierProduct extends Model
{
    use HasFactory;

    protected $table = 'supplier_products';

    protected $fillable = [
        'supplier_id',
        'product_id',
        'sku_id',
        'supply_price',
        'min_order_qty',
        'delivery_days',
        'is_primary',
        'valid_from',
        'valid_to',
    ];

    protected $casts = [
        'supply_price' => 'decimal:2',
        'min_order_qty' => 'decimal:2',
        'valid_from' => 'date',
        'valid_to' => 'date',
    ];

    // 关联供应商
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
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
