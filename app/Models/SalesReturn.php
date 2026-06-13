<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesReturn extends Model
{
    use HasFactory;

    protected $table = 'sales_returns';

    protected $fillable = [
        'return_no',
        'order_id',
        'customer_id',
        'return_type',
        'reason',
        'reason_detail',
        'images',
        'quantity',
        'amount',
        'status',
        'warehouse_id',
        'employee_id',
        'approved_by',
        'approved_at',
        'received_at',
        'refunded_at',
        'refund_method',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'amount' => 'decimal:2',
        'images' => 'array',
        'approved_at' => 'datetime',
        'received_at' => 'datetime',
        'refunded_at' => 'datetime',
    ];

    // 退货类型常量
    const TYPE_RETURN_REFUND = 1;   // 退货退款
    const TYPE_REFUND_ONLY = 2;    // 仅退款
    const TYPE_EXCHANGE = 3;       // 换货

    // 状态常量
    const STATUS_APPLYING = 0;
    const STATUS_APPROVED = 1;
    const STATUS_RECEIVED = 2;
    const STATUS_REFUNDED = 3;
    const STATUS_REJECTED = 4;
    const STATUS_CANCELLED = 5;
    const STATUS_EXCHANGE_SENT = 6;

    // 关联订单
    public function order()
    {
        return $this->belongsTo(SalesOrder::class);
    }

    // 关联客户
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    // 关联仓库
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    // 关联处理人
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    // 关联审批人
    public function approver()
    {
        return $this->belongsTo(Employee::class, 'approved_by');
    }

    // 关联明细
    public function items()
    {
        return $this->hasMany(SalesReturnItem::class, 'return_id');
    }

    // 关联退款记录
    public function refundRecords()
    {
        return $this->hasMany(RefundRecord::class);
    }
}
