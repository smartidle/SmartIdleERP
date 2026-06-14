<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApprovalNode extends Model
{
    protected $table = 'approval_nodes';

    protected $fillable = [
        'flow_id', 'name', 'node_order', 'node_type',
        'approver_id', 'role_id',
    ];

    public function flow()
    {
        return $this->belongsTo(ApprovalFlow::class, 'flow_id');
    }
}
