<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkOrderOperation extends Model
{
    protected $table = 'work_order_operations';

    protected $fillable = [
        'work_order_id', 'operation_seq', 'operation_name', 'work_center',
        'standard_hours', 'actual_hours', 'status',
        'start_time', 'end_time', 'worker_id',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    const STATUS_PENDING = 0;
    const STATUS_IN_PROGRESS = 1;
    const STATUS_COMPLETED = 2;

    public function workOrder()
    {
        return $this->belongsTo(WorkOrder::class, 'work_order_id');
    }
}
