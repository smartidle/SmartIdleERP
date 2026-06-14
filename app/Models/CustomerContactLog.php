<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerContactLog extends Model
{
    protected $table = 'customer_contact_logs';

    protected $fillable = [
        'customer_id', 'employee_id', 'contact_type',
        'subject', 'content', 'contact_time', 'next_follow_up',
    ];

    protected $casts = [
        'contact_time' => 'datetime',
        'next_follow_up' => 'date',
    ];

    public function customer() { return $this->belongsTo(Customer::class); }
    public function employee() { return $this->belongsTo(Employee::class); }
}
