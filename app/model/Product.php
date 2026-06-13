<?php

namespace app\model;

use think\Model;
use think\facade\Db;

/**
 * 产品模型
 */
class Product extends Model
{
    // 表名
    protected $name = 'product';

    // 自动时间戳
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    // 软删除
    protected $deleteTime = 'delete_time';

    // 类型转换
    protected $type = [
        'category_id' => 'integer',
        'has_spec' => 'integer',
        'is_bom' => 'integer',
        'status' => 'integer',
        'base_cost_price' => 'float',
        'base_sale_price' => 'float',
        'base_wholesale_price' => 'float',
    ];

    /**
     * 图片列表获取器
     */
    public function getImagesAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * 图片列表设置器
     */
    public function setImagesAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    /**
     * 分类关联
     */
    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    /**
     * SKU列表
     */
    public function skus()
    {
        return $this->hasMany(ProductSku::class, 'product_id');
    }

    /**
     * 规格列表
     */
    public function specs()
    {
        return $this->hasMany(ProductSpec::class, 'product_id');
    }

    /**
     * 库存记录
     */
    public function inventories()
    {
        return $this->hasMany(Inventory::class, 'product_id');
    }

    /**
     * 获取SKU列表（带规格组合）
     */
    public function getSkuList(): array
    {
        $skus = $this->skus()->where('status', 1)->select();
        $specs = $this->specs()->order('sort', 'asc')->select();

        // 组合规格数据
        $specData = [];
        foreach ($specs as $spec) {
            $specData[] = [
                'id' => $spec->id,
                'name' => $spec->spec_name,
                'values' => json_decode($spec->spec_values, true) ?: [],
                'is_color' => $spec->is_color,
                'is_size' => $spec->is_size,
            ];
        }

        return [
            'product' => $this,
            'specs' => $specData,
            'skus' => $skus,
        ];
    }

    /**
     * 获取可用库存数量
     */
    public function getAvailableStock(): float
    {
        return Db::name('inventory')
            ->where('product_id', $this->id)
            ->sum('quantity - locked_quantity');
    }

    /**
     * 搜索器：关键词搜索
     */
    public function searchKeywordAttr($query, $value)
    {
        if (!empty($value)) {
            $query->whereLike('name|sku_prefix|brand', "%{$value}%");
        }
    }

    /**
     * 搜索器：状态筛选
     */
    public function searchStatusAttr($query, $value)
    {
        if ($value !== '') {
            $query->where('status', $value);
        }
    }

    /**
     * 搜索器：分类筛选
     */
    public function searchCategoryIdAttr($query, $value)
    {
        if (!empty($value)) {
            $query->where('category_id', $value);
        }
    }
}
