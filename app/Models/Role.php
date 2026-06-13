<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $table = 'roles';

    protected $fillable = [
        'name',
        'code',
        'description',
        'status',
    ];

    // 关联权限
    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'role_permissions');
    }

    // 关联数据权限
    public function dataPermissions()
    {
        return $this->hasMany(DataPermission::class, 'role_id');
    }

    // 关联员工
    public function employees()
    {
        return $this->hasMany(Employee::class, 'role_id');
    }

    // 同步权限
    public function syncPermissions($permissionIds)
    {
        $this->permissions()->sync($permissionIds);
    }

    // 是否拥有权限
    public function hasPermission($permissionCode)
    {
        return $this->permissions()->where('code', $permissionCode)->exists();
    }
}
