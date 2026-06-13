<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductSpec extends Model
{
    use HasFactory;

    protected $table = 'product_specs';

    protected $fillable = [
        'product_id',
        'spec_name',
        'spec_values',
        'is_color',
        'is_size',
        'spec_type',
        'spec_image_mode',
        'sort',
    ];

    protected $casts = [
        'spec_values' => 'array',
        'sort' => 'integer',
    ];

    // 规格类型常量
    const TYPE_OPTION = 1;    // 选项
    const TYPE_INPUT = 2;     // 输入

    // 关联产品
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // 获取规格值列表
    public function getValuesListAttribute()
    {
        return is_array($this->spec_values) ? $this->spec_values : json_decode($this->spec_values, true) ?? [];
    }
}
