<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseReturn extends Model
{
    protected $table = 'purchase_returns';

    protected $fillable = [
        'return_no', 'receive_id', 'order_id', 'supplier_id',
        'reason', 'total_amount', 'status',
        'employee_id', 'approver_id', 'approved_at',
        'receiver_id', 'received_at', 'remark',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'status' => 'integer',
    ];

    // Status: 1=待审核 2=已审核 3=已退货 4=已拒绝
    const STATUS_PENDING = 1;
    const STATUS_APPROVED = 2;
    const STATUS_RETURNED = 3;
    const STATUS_REJECTED = 4;

    public function receive()
    {
        return $this->belongsTo(PurchaseReceive::class, 'receive_id');
    }

    public function order()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function approver()
    {
        return $this->belongsTo(Employee::class, 'approver_id');
    }

    public function receiver()
    {
        return $this->belongsTo(Employee::class, 'receiver_id');
    }

    public function items()
    {
        return $this->hasMany(PurchaseReturnItem::class, 'return_id');
    }
}