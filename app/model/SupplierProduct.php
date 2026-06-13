<?php

namespace app\model;

use think\Model;

/**
 * 供应商产品报价模型
 */
class SupplierProduct extends Model
{
    // 表名
    protected $name = 'supplier_product';

    // 自动时间戳
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';

    // 类型转换
    protected $type = [
        'supplier_id' => 'integer',
        'product_id' => 'integer',
        'sku_id' => 'integer',
        'supply_price' => 'float',
        'min_order_qty' => 'float',
        'delivery_days' => 'integer',
        'is_primary' => 'integer',
    ];

    /**
     * 供应商关联
     */
    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
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
     * 检查报价是否有效
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
