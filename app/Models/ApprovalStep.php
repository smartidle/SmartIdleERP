<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApprovalStep extends Model
{
    protected $table = 'approval_steps';

    protected $fillable = [
        'record_id',
        'flow_id',
        'node_id',
        'step_no',
        'approver_id',
        'status',
        'comment',
        'approved_at',
    ];

    protected $casts = [
        'step_no' => 'integer',
        'status' => 'integer',
    ];

    public function record()
    {
        return $this->belongsTo(ApprovalRecord::class, 'record_id');
    }

    public function approver()
    {
        return $this->belongsTo(Employee::class, 'approver_id');
    }

    public function flow()
    {
        return $this->belongsTo(ApprovalFlow::class, 'flow_id');
    }

    public function node()
    {
        return $this->belongsTo(ApprovalNode::class, 'node_id');
    }
}
