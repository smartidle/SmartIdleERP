<?php

namespace app\model;

use think\Model;

/**
 * 客户专属价格模型
 */
class CustomerPrice extends Model
{
    // 表名
    protected $name = 'customer_price';

    // 自动时间戳
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';

    // 类型转换
    protected $type = [
        'customer_id' => 'integer',
        'product_id' => 'integer',
        'sku_id' => 'integer',
        'price' => 'float',
        'discount_rate' => 'float',
    ];

    /**
     * 客户关联
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /**
     * 产品关联
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * SKU关联
     */
    public function sku()
    {
        return $this->belongsTo(ProductSku::class, 'sku_id');
    }

    /**
     * 检查价格是否有效
     */
    public function isValid(): bool
    {
        $now = date('Y-m-d');
        $validFrom = $this->valid_from;
        $validTo = $this->valid_to;

        if ($validFrom && $validFrom > $now) {
            return false;
        }

        if ($validTo && $validTo < $now) {
            return false;
        }

        return true;
    }
}
