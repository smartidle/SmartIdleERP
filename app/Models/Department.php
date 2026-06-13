<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasFactory;

    protected $table = 'departments';

    protected $fillable = [
        'parent_id',
        'name',
        'code',
        'manager_id',
        'status',
    ];

    // 关联父部门
    public function parent()
    {
        return $this->belongsTo(Department::class, 'parent_id');
    }

    // 关联子部门
    public function children()
    {
        return $this->hasMany(Department::class, 'parent_id');
    }

    // 关联负责人
    public function manager()
    {
        return $this->belongsTo(Employee::class, 'manager_id');
    }

    // 关联员工
    public function employees()
    {
        return $this->hasMany(Employee::class, 'department_id');
    }
}
