<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'products';

    protected $fillable = [
        'sku_prefix',
        'name',
        'name_i18n',
        'category_id',
        'brand',
        'base_unit',
        'base_cost_price',
        'base_sale_price',
        'base_wholesale_price',
        'weight',
        'length_cm',
        'width_cm',
        'height_cm',
        'volume_m3',
        'min_stock',
        'max_stock',
        'shelf_life_days',
        'min_pack_qty',
        'image',
        'images',
        'description',
        'description_i18n',
        'is_bom',
        'has_spec',
        'status',
    ];

    protected $casts = [
        'base_cost_price' => 'decimal:2',
        'base_sale_price' => 'decimal:2',
        'base_wholesale_price' => 'decimal:2',
        'weight' => 'decimal:3',
        'length_cm' => 'decimal:2',
        'width_cm' => 'decimal:2',
        'height_cm' => 'decimal:2',
        'volume_m3' => 'decimal:6',
        'min_stock' => 'decimal:2',
        'max_stock' => 'decimal:2',
        'images' => 'array',
    ];

    // 关联分类
    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    // 关联规格定义
    public function specs()
    {
        return $this->hasMany(ProductSpec::class);
    }

    // 关联SKU
    public function skus()
    {
        return $this->hasMany(ProductSku::class);
    }

    // 关联BOM(作为成品)
    public function boms()
    {
        return $this->hasMany(Bom::class, 'product_id');
    }

    // 关联库存
    public function inventories()
    {
        return $this->hasMany(Inventory::class);
    }

    // 获取启用的SKU
    public function activeSkus()
    {
        return $this->skus()->where('status', 1);
    }

    // 检查是否有规格
    public function hasSpecifications()
    {
        return $this->has_spec == 1 || $this->skus()->count() > 0;
    }
}
