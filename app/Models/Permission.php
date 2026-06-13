<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    use HasFactory;

    protected $table = 'permissions';

    protected $fillable = [
        'parent_id',
        'name',
        'code',
        'type',
        'route',
        'status',
    ];

    // 类型常量
    const TYPE_MENU = 1;
    const TYPE_ACTION = 2;
    const TYPE_API = 3;

    // 关联父权限
    public function parent()
    {
        return $this->belongsTo(Permission::class, 'parent_id');
    }

    // 关联子权限
    public function children()
    {
        return $this->hasMany(Permission::class, 'parent_id');
    }

    // 关联角色
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_permissions');
    }
}
