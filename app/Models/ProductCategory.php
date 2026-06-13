<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductCategory extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'product_categories';

    protected $fillable = [
        'parent_id',
        'name',
        'name_i18n',
        'code',
        'level',
        'sort',
        'status',
    ];

    protected $casts = [
        'sort' => 'integer',
        'level' => 'integer',
    ];

    // 关联父分类
    public function parent()
    {
        return $this->belongsTo(ProductCategory::class, 'parent_id');
    }

    // 关联子分类
    public function children()
    {
        return $this->hasMany(ProductCategory::class, 'parent_id');
    }

    // 关联产品
    public function products()
    {
        return $this->hasMany(Product::class, 'category_id');
    }

    // 获取完整路径名称
    public function getFullPathAttribute()
    {
        $path = [$this->name];
        $parent = $this->parent;
        while ($parent) {
            array_unshift($path, $parent->name);
            $parent = $parent->parent;
        }
        return implode(' / ', $path);
    }
}
