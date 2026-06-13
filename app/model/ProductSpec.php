<?php

namespace app\model;

use think\Model;

/**
 * 产品规格模型
 */
class ProductSpec extends Model
{
    // 表名
    protected $name = 'product_spec';

    // 自动时间戳
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';

    // 类型转换
    protected $type = [
        'product_id' => 'integer',
        'is_color' => 'integer',
        'is_size' => 'integer',
        'spec_type' => 'integer',
        'spec_image_mode' => 'integer',
        'sort' => 'integer',
    ];

    /**
     * 规格值获取器
     */
    public function getSpecValuesAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * 规格值设置器
     */
    public function setSpecValuesAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    /**
     * 产品关联
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * SKU列表
     */
    public function skus()
    {
        return $this->hasMany(ProductSku::class, 'product_id');
    }
}
