<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DataPermission extends Model
{
    use HasFactory;

    protected $table = 'data_permissions';

    protected $fillable = [
        'role_id',
        'module',
        'scope_type',
    ];

    // 范围类型常量
    const SCOPE_SELF = 1;      // 本人
    const SCOPE_DEPT = 2;     // 本部门
    const SCOPE_ALL = 3;      // 全部

    // 关联角色
    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }
}
