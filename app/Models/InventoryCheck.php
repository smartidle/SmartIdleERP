<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryCheck extends Model
{
    protected $table = 'inventory_checks';

    protected $fillable = [
        'check_no', 'warehouse_id', 'check_date', 'type',
        'status', 'remark', 'employee_id', 'approver_id', 'approved_at',
    ];

    protected $casts = [
        'status' => 'integer',
        'type' => 'integer',
    ];

    const STATUS_IN_PROGRESS = 1;
    const STATUS_PENDING = 2;
    const STATUS_APPROVED = 3;
    const STATUS_REJECTED = 4;

    const TYPE_FULL = 1;
    const TYPE_SAMPLE = 2;

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function approver()
    {
        return $this->belongsTo(Employee::class, 'approver_id');
    }

    public function items()
    {
        return $this->hasMany(\App\Models\InventoryCheckItem::class, 'check_id');
    }
}