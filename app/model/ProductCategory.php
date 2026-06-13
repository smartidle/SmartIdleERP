<?php

namespace app\model;

use think\Model;

/**
 * 产品分类模型
 */
class ProductCategory extends Model
{
    // 表名
    protected $name = 'product_category';

    // 自动时间戳
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';

    // 软删除
    protected $deleteTime = 'delete_time';

    // 类型转换
    protected $type = [
        'parent_id' => 'integer',
        'level' => 'integer',
        'sort' => 'integer',
        'status' => 'integer',
    ];

    /**
     * 多语言名称获取器
     */
    public function getNameI18nAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * 父分类
     */
    public function parent()
    {
        return $this->belongsTo(ProductCategory::class, 'parent_id');
    }

    /**
     * 子分类
     */
    public function children()
    {
        return $this->hasMany(ProductCategory::class, 'parent_id');
    }

    /**
     * 产品列表
     */
    public function products()
    {
        return $this->hasMany(Product::class, 'category_id');
    }

    /**
     * 获取分类树
     */
    public static function getTree(int $parentId = 0): array
    {
        $list = self::where('parent_id', $parentId)
            ->where('status', 1)
            ->order('sort', 'asc')
            ->select()
            ->toArray();

        $result = [];
        foreach ($list as $item) {
            $children = self::getTree($item['id']);
            $item['children'] = $children;
            $result[] = $item;
        }

        return $result;
    }

    /**
     * 获取所有子分类ID
     */
    public function getAllChildIds(): array
    {
        $ids = [];
        $children = self::where('parent_id', $this->id)->column('id');

        foreach ($children as $childId) {
            $ids[] = $childId;
            $child = self::find($childId);
            if ($child) {
                $ids = array_merge($ids, $child->getAllChildIds());
            }
        }

        return $ids;
    }

    /**
     * 获取完整路径名称
     */
    public function getFullPath(): string
    {
        $path = [$this->name];
        $parent = $this->parent;

        while ($parent) {
            array_unshift($path, $parent->name);
            $parent = $parent->parent;
        }

        return implode(' > ', $path);
    }
}
