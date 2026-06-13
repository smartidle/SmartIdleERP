<?php

namespace app\model;

use think\Model;
use think\facade\Db;

/**
 * 员工模型
 */
class Employee extends Model
{
    // 表名
    protected $name = 'employee';

    // 自动时间戳
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    // 软删除
    protected $deleteTime = 'delete_time';

    // 隐藏字段
    protected $hidden = ['password'];

    // 类型转换
    protected $type = [
        'status' => 'integer',
        'role_id' => 'integer',
        'department_id' => 'integer',
    ];

    /**
     * 部门关联
     */
    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    /**
     * 角色关联
     */
    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    /**
     * 获取员工权限列表
     */
    public function getPermissions(): array
    {
        if (empty($this->role_id)) {
            return [];
        }

        return Db::name('role_permission')
            ->alias('rp')
            ->join('permission p', 'p.id = rp.permission_id')
            ->where('rp.role_id', $this->role_id)
            ->where('p.status', 1)
            ->column('p.code');
    }

    /**
     * 验证密码
     */
    public function checkPassword(string $password): bool
    {
        return password_verify($password, $this->password);
    }

    /**
     * 设置密码
     */
    public function setPassword(string $password): void
    {
        $this->password = password_hash($password, PASSWORD_BCRYPT);
    }

    /**
     * 搜索器：关键词搜索
     */
    public function searchKeywordAttr($query, $value)
    {
        if (!empty($value)) {
            $query->whereLike('name|code|email', "%{$value}%");
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
     * 搜索器：部门筛选
     */
    public function searchDepartmentIdAttr($query, $value)
    {
        if (!empty($value)) {
            $query->where('department_id', $value);
        }
    }
}
