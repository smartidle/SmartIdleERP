<?php

namespace app\model;

use think\Model;
use think\facade\Db;

/**
 * 角色模型
 */
class Role extends Model
{
    // 表名
    protected $name = 'role';

    // 自动时间戳
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';

    // 类型转换
    protected $type = [
        'status' => 'integer',
        'sort' => 'integer',
    ];

    /**
     * 权限关联
     */
    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'role_permission', 'permission_id', 'role_id');
    }

    /**
     * 设置权限
     */
    public function setPermissions(array $permissionIds): void
    {
        // 删除现有权限
        Db::name('role_permission')->where('role_id', $this->id)->delete();

        // 添加新权限
        $data = array_map(function ($permissionId) {
            return [
                'role_id' => $this->id,
                'permission_id' => $permissionId,
            ];
        }, $permissionIds);

        Db::name('role_permission')->insertAll($data);
    }

    /**
     * 获取权限ID列表
     */
    public function getPermissionIds(): array
    {
        return Db::name('role_permission')
            ->where('role_id', $this->id)
            ->column('permission_id');
    }
}
