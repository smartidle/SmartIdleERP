<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RefundRecord extends Model
{
    protected $table = 'refund_records';

    protected $fillable = [
        'sales_return_id', 'amount', 'refund_method',
        'transaction_no', 'status', 'employee_id', 'refund_time', 'notes',
    ];

    protected $casts = ['refund_time' => 'datetime'];

    const STATUS_PENDING = 0;
    const STATUS_COMPLETED = 1;
    const STATUS_CANCELLED = 2;

    public function salesReturn() { return $this->belongsTo(SalesReturn::class, 'sales_return_id'); }
}
