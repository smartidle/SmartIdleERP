<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OperationLog extends Model
{
    public $timestamps = false;
    protected $table = 'operation_logs';

    protected $fillable = [
        'employee_id', 'module', 'action', 'target_type', 'target_id',
        'description', 'ip', 'create_time',
    ];

    protected $casts = ['create_time' => 'datetime'];
}
