<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinanceReceipt extends Model
{
    use HasFactory;

    protected $table = 'finance_receipts';

    protected $fillable = [
        'receipt_no',
        'receipt_type',
        'order_id',
        'customer_id',
        'amount',
        'advance_receipt_id',
        'payment_method',
        'payment_date',
        'invoice_no',
        'account_id',
        'reconcile_status',
        'status',
        'notes',
        'employee_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    // 类型常量
    const TYPE_SALES = 1;
    const TYPE_ADVANCE = 2;
    const TYPE_REFUND = 3;
    const TYPE_OTHER = 9;

    // 关联客户
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    // 关联订单
    public function order()
    {
        return $this->belongsTo(SalesOrder::class);
    }

    // 关联经办人
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
