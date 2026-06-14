<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApprovalInstance extends Model
{
    protected $table = 'approval_instances';

    protected $fillable = [
        'flow_id', 'related_type', 'related_id',
        'initiator_id', 'status',
    ];

    protected $casts = [
        'status' => 'integer',
    ];

    public function flow()
    {
        return $this->belongsTo(ApprovalFlow::class, 'flow_id');
    }

    public function records()
    {
        return $this->hasMany(ApprovalRecord::class, 'instance_id');
    }
}
