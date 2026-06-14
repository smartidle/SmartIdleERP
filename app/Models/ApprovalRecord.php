<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApprovalRecord extends Model
{
    protected $table = 'approval_records';

    protected $fillable = [
        'flow_id',
        'order_type',
        'order_id',
        'applicant_id',
        'approver_id',
        'total_steps',
        'current_step',
        'status',
        'completed_at',
        'comment',
    ];

    protected $casts = [
        'status' => 'integer',
        'total_steps' => 'integer',
        'current_step' => 'integer',
    ];

    // Status: 1=审批中 2=已通过 3=已拒绝
    const STATUS_PENDING = 1;
    const STATUS_APPROVED = 2;
    const STATUS_REJECTED = 3;

    public function flow()
    {
        return $this->belongsTo(ApprovalFlow::class, 'flow_id');
    }

    public function applicant()
    {
        return $this->belongsTo(Employee::class, 'applicant_id');
    }

    public function approver()
    {
        return $this->belongsTo(Employee::class, 'approver_id');
    }

    public function steps()
    {
        return $this->hasMany(ApprovalStep::class, 'record_id')->orderBy('step_no');
    }

    public function approvalSteps()
    {
        return $this->hasMany(ApprovalStep::class, 'record_id')->orderBy('step_no');
    }

    /**
     * 获取当前步骤
     */
    public function currentStep()
    {
        return $this->hasOne(ApprovalStep::class, 'record_id')
            ->where('step_no', $this->current_step)
            ->latest();
    }

    /**
     * 获取下一步骤
     */
    public function nextStep()
    {
        return $this->hasOne(ApprovalStep::class, 'record_id')
            ->where('step_no', $this->current_step + 1)
            ->latest();
    }
}
