<?php

namespace app\model;

/**
 * BOM明细模型
 */
class BomItem extends \think\Model
{
    // 表名
    protected $name = 'bom_item';

    // 自动时间戳
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';

    // 类型转换
    protected $type = [
        'bom_id' => 'integer',
        'product_id' => 'integer',
        'sku_id' => 'integer',
        'quantity' => 'float',
        'loss_rate' => 'float',
        'actual_quantity' => 'float',
    ];

    /**
     * BOM关联
     */
    public function bom()
    {
        return $this->belongsTo(Bom::class, 'bom_id');
    }

    /**
     * 原材料产品
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * 原材料SKU
     */
    public function sku()
    {
        return $this->belongsTo(ProductSku::class, 'sku_id');
    }

    /**
     * 计算实际用量（含损耗）
     */
    public function calculateActualQuantity(): float
    {
        $lossRate = $this->loss_rate ?? 0;
        return round($this->quantity * (1 + $lossRate / 100), 4);
    }
}
