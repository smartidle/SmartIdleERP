<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinancePayment extends Model
{
    use HasFactory;

    protected $table = 'finance_payments';

    protected $fillable = [
        'payment_no',
        'payment_type',
        'order_id',
        'supplier_id',
        'amount',
        'payment_method',
        'payment_date',
        'invoice_no',
        'account_id',
        'status',
        'notes',
        'employee_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    // 类型常量
    const TYPE_PURCHASE = 1;
    const TYPE_ADVANCE = 2;
    const TYPE_REFUND = 3;
    const TYPE_OTHER = 9;

    // 关联供应商
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    // 关联订单
    public function order()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    // 关联经办人
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
