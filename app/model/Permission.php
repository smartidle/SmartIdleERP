<?php

namespace app\model;

use think\Model;

/**
 * 权限模型
 */
class Permission extends Model
{
    // 表名
    protected $name = 'permission';

    // 自动时间戳
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';

    // 类型转换
    protected $type = [
        'parent_id' => 'integer',
        'type' => 'integer',
        'sort' => 'integer',
        'status' => 'integer',
    ];

    // 权限类型常量
    const TYPE_MENU = 1;   // 菜单
    const TYPE_ACTION = 2; // 操作
    const TYPE_API = 3;    // API

    /**
     * 父权限
     */
    public function parent()
    {
        return $this->belongsTo(Permission::class, 'parent_id');
    }

    /**
     * 子权限
     */
    public function children()
    {
        return $this->hasMany(Permission::class, 'parent_id');
    }

    /**
     * 角色关联
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_permission', 'role_id', 'permission_id');
    }

    /**
     * 获取权限树
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
}
