<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Employee extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'employees';

    protected $fillable = [
        'code',
        'name',
        'email',
        'password',
        'department_id',
        'position',
        'role_id',
        'status',
        'preferred_lang',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'password' => 'hashed',
    ];

    // 关联部门
    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    // 关联角色
    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    // 关联负责的部门
    public function managedDepartments()
    {
        return $this->hasMany(Department::class, 'manager_id');
    }

    // 关联负责的仓库
    public function managedWarehouses()
    {
        return $this->hasMany(Warehouse::class, 'manager_id');
    }
}
