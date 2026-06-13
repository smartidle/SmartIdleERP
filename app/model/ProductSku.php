<?php

namespace app\model;

use think\Model;
use think\facade\Db;

/**
 * 产品SKU模型
 */
class ProductSku extends Model
{
    // 表名
    protected $name = 'product_sku';

    // 自动时间戳
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';

    // 软删除
    protected $deleteTime = 'delete_time';

    // 类型转换
    protected $type = [
        'product_id' => 'integer',
        'status' => 'integer',
        'cost_price' => 'float',
        'sale_price' => 'float',
        'wholesale_price' => 'float',
    ];

    /**
     * 规格组合获取器
     */
    public function getSpecCombinationAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * 规格组合设置器
     */
    public function setSpecCombinationAttr($value)
    {
        if (is_array($value)) {
            $this->spec_hash = md5(json_encode($value, JSON_UNESCAPED_UNICODE));
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }
        return $value;
    }

    /**
     * 产品关联
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * 库存记录
     */
    public function inventories()
    {
        return $this->hasMany(Inventory::class, 'sku_id');
    }

    /**
     * 获取可用库存数量
     */
    public function getAvailableStock(int $warehouseId = 0): float
    {
        $query = Db::name('inventory')
            ->where('sku_id', $this->id);

        if ($warehouseId > 0) {
            $query->where('warehouse_id', $warehouseId);
        }

        return $query->sum('quantity - locked_quantity');
    }

    /**
     * 获取有效成本价（优先SKU价格，否则取产品基础价）
     */
    public function getEffectiveCostPrice(): float
    {
        if ($this->cost_price !== null) {
            return (float) $this->cost_price;
        }

        $product = $this->product;
        return $product ? (float) $product->base_cost_price : 0;
    }

    /**
     * 获取有效销售价（优先SKU价格，否则取产品基础价）
     */
    public function getEffectiveSalePrice(): float
    {
        if ($this->sale_price !== null) {
            return (float) $this->sale_price;
        }

        $product = $this->product;
        return $product ? (float) $product->base_sale_price : 0;
    }

    /**
     * 生成SKU编码
     */
    public static function generateSkuCode(string $productPrefix, array $specCombination): string
    {
        $hash = md5(json_encode($specCombination, JSON_UNESCAPED_UNICODE));
        return $productPrefix . '-' . strtoupper(substr($hash, 0, 8));
    }
}
