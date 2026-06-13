<?php

namespace app\model;

/**
 * BOM物料清单模型
 */
class Bom extends \think\Model
{
    // 表名
    protected $name = 'bom';

    // 自动时间戳
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    // 类型转换
    protected $type = [
        'product_id' => 'integer',
        'sku_id' => 'integer',
        'quantity' => 'float',
        'unit_cost' => 'float',
        'status' => 'integer',
    ];

    // BOM状态常量
    const STATUS_DRAFT = 0;    // 草稿
    const STATUS_ACTIVE = 1;  // 生效
    const STATUS_INACTIVE = 2; // 失效

    /**
     * 产品关联
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * 成品SKU
     */
    public function sku()
    {
        return $this->belongsTo(ProductSku::class, 'sku_id');
    }

    /**
     * BOM明细
     */
    public function items()
    {
        return $this->hasMany(BomItem::class, 'bom_id');
    }

    /**
     * 生成BOM编号
     */
    public static function generateCode(): string
    {
        $prefix = 'BOM';
        $date = date('Ymd');
        $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        return $prefix . $date . $random;
    }

    /**
     * 计算标准成本
     */
    public function calculateStandardCost(): float
    {
        $totalCost = 0;
        foreach ($this->items as $item) {
            $product = $item->product;
            $costPrice = $product ? $product->base_cost_price : 0;
            $totalCost += $costPrice * $item->actual_quantity;
        }

        // 除以产出数量得到单位成本
        return $this->quantity > 0 ? round($totalCost / $this->quantity, 4) : 0;
    }
}
