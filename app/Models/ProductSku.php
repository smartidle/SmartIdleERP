<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductSku extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'product_skus';

    protected $fillable = [
        'product_id',
        'sku_code',
        'barcode',
        'spec_combination',
        'spec_hash',
        'cost_price',
        'sale_price',
        'wholesale_price',
        'image',
        'weight',
        'status',
    ];

    protected $casts = [
        'spec_combination' => 'array',
        'cost_price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'wholesale_price' => 'decimal:2',
        'weight' => 'decimal:3',
    ];

    // 关联产品
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // 关联库存
    public function inventories()
    {
        return $this->hasMany(Inventory::class);
    }

    // 获取规格组合描述
    public function getSpecDescriptionAttribute()
    {
        $specs = $this->spec_combination ?? [];
        $parts = [];
        foreach ($specs as $name => $value) {
            $parts[] = "{$name}: {$value}";
        }
        return implode(', ', $parts);
    }

    // 获取实际成本价
    public function getRealCostPriceAttribute()
    {
        return $this->cost_price ?? $this->product->base_cost_price;
    }

    // 获取实际销售价
    public function getRealSalePriceAttribute()
    {
        return $this->sale_price ?? $this->product->base_sale_price;
    }

    // 获取总库存
    public function getTotalStockAttribute()
    {
        return $this->inventories()->sum('quantity');
    }
}
