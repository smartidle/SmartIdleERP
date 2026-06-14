<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkOrderReport extends Model
{
    protected $table = 'work_order_reports';

    protected $fillable = [
        'work_order_id', 'report_qty', 'qualified_qty',
        'defective_qty', 'report_date', 'reporter_id', 'notes',
    ];

    protected $casts = [
        'report_date' => 'date',
    ];

    public function workOrder()
    {
        return $this->belongsTo(WorkOrder::class, 'work_order_id');
    }
}
