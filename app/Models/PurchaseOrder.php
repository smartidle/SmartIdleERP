<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    use HasFactory;

    protected $table = 'purchase_orders';

    protected $fillable = [
        'order_no',
        'inquiry_id',
        'supplier_id',
        'order_date',
        'expected_date',
        'warehouse_id',
        'status',
        'subtotal',
        'tax_amount',
        'total_amount',
        'paid_amount',
        'payment_status',
        'notes',
        'employee_id',
        'approver_id',
        'approved_at',
    ];

    protected $casts = [
        'order_date' => 'date',
        'expected_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    // 状态常量
    const STATUS_DRAFT = 0;
    const STATUS_PENDING = 1;
    const STATUS_APPROVED = 2;
    const STATUS_PARTIAL = 3;
    const STATUS_RECEIVED = 4;
    const STATUS_COMPLETED = 5;
    const STATUS_CANCELLED = 6;

    // 关联询价单
    public function inquiry()
    {
        return $this->belongsTo(PurchaseInquiry::class);
    }

    // 关联供应商
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    // 关联仓库
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    // 关联采购员
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
        return $this->hasMany(PurchaseOrderItem::class, 'order_id');
    }

    // 关联收货单
    public function receives()
    {
        return $this->hasMany(PurchaseReceive::class);
    }
}
