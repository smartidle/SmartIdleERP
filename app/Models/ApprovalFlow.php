<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApprovalFlow extends Model
{
    protected $table = 'approval_flows';

    protected $fillable = [
        'name', 'module', 'trigger_condition', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'integer',
    ];

    public function nodes()
    {
        return $this->hasMany(ApprovalNode::class, 'flow_id');
    }

    public function instances()
    {
        return $this->hasMany(ApprovalInstance::class, 'flow_id');
    }
}
