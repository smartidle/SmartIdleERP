<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApprovalDelegate extends Model
{
    protected $table = 'approval_delegates';

    protected $fillable = [
        'delegator_id', 'delegate_id', 'start_date',
        'end_date', 'module', 'status',
    ];

    protected $casts = [
        'status' => 'integer',
    ];

    public function delegator()
    {
        return $this->belongsTo(Employee::class, 'delegator_id');
    }

    public function delegate()
    {
        return $this->belongsTo(Employee::class, 'delegate_id');
    }
}
