<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesOrder extends Model
{
    use HasFactory;

    protected $table = 'sales_orders';

    protected $fillable = [
        'order_no',
        'customer_id',
        'quote_id',
        'source',
        'order_date',
        'delivery_date',
        'warehouse_id',
        'status',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'promotion_amount',
        'coupon_amount',
        'total_amount',
        'paid_amount',
        'payment_status',
        'shipping_contact',
        'shipping_phone',
        'shipping_address',
        'shipping_fee',
        'notes',
        'employee_id',
        'approver_id',
        'approved_at',
        'shipped_at',
        'completed_at',
    ];

    protected $casts = [
        'order_date' => 'date',
        'delivery_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'promotion_amount' => 'decimal:2',
        'coupon_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'shipping_fee' => 'decimal:2',
        'approved_at' => 'datetime',
        'shipped_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    // 订单状态常量
    const STATUS_DRAFT = 0;
    const STATUS_PENDING = 1;      // 待审批
    const STATUS_APPROVED = 2;    // 已审批
    const STATUS_PARTIAL = 3;     // 部分发货
    const STATUS_SHIPPED = 4;     // 已发货
    const STATUS_COMPLETED = 5;   // 已完成
    const STATUS_CANCELLED = 6;   // 已取消

    // 来源常量
    const SOURCE_MANUAL = 1;
    const SOURCE_QUOTE = 2;
    const SOURCE_ECOMMERCE = 3;

    // 付款状态常量
    const PAY_UNPAID = 0;
    const PAY_PARTIAL = 1;
    const PAY_PAID = 2;

    // 关联客户
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    // 关联报价单
    public function quote()
    {
        return $this->belongsTo(SalesQuote::class);
    }

    // 关联仓库
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    // 关联销售员
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    // 关联审批人
    public function approver()
    {
        return $this->belongsTo(Employee::class, 'approver_id');
    }

    // 关联明细
    public function items()
    {
        return $this->hasMany(SalesOrderItem::class, 'order_id');
    }

    // 关联促销记录
    public function promotions()
    {
        return $this->hasMany(OrderPromotion::class);
    }

    // 关联发货单
    public function deliveries()
    {
        return $this->hasMany(SalesDelivery::class);
    }

    // 关联收款单
    public function receipts()
    {
        return $this->hasMany(FinanceReceipt::class);
    }

    // 获取未收款金额
    public function getUnpaidAmountAttribute()
    {
        return max(0, $this->total_amount - $this->paid_amount);
    }

    // 检查是否可编辑
    public function canEdit()
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_PENDING]);
    }

    // 检查是否可审批
    public function canApprove()
    {
        return $this->status == self::STATUS_PENDING;
    }

    // 检查是否可发货
    public function canDeliver()
    {
        return $this->status == self::STATUS_APPROVED;
    }
}
