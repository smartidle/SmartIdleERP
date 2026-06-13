<?php

namespace app\model;

use think\Model;

/**
 * 部门模型
 */
class Department extends Model
{
    // 表名
    protected $name = 'department';

    // 自动时间戳
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    // 类型转换
    protected $type = [
        'parent_id' => 'integer',
        'manager_id' => 'integer',
        'status' => 'integer',
        'sort' => 'integer',
    ];

    /**
     * 父部门关联
     */
    public function parent()
    {
        return $this->belongsTo(Department::class, 'parent_id');
    }

    /**
     * 子部门
     */
    public function children()
    {
        return $this->hasMany(Department::class, 'parent_id');
    }

    /**
     * 负责人
     */
    public function manager()
    {
        return $this->belongsTo(Employee::class, 'manager_id');
    }

    /**
     * 获取部门树
     */
    public static function getTree(int $parentId = 0, array $where = []): array
    {
        $list = self::where('parent_id', $parentId)
            ->where($where)
            ->order('sort', 'asc')
            ->select()
            ->toArray();

        $result = [];
        foreach ($list as $item) {
            $children = self::getTree($item['id'], $where);
            $item['children'] = $children;
            $result[] = $item;
        }

        return $result;
    }

    /**
     * 获取所有子部门ID
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
}
