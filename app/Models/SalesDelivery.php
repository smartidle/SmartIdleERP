<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesDelivery extends Model
{
    use HasFactory;

    protected $table = 'sales_deliveries';

    protected $fillable = [
        'delivery_no',
        'order_id',
        'warehouse_id',
        'delivery_date',
        'status',
        'is_split',
        'parent_id',
        'package_no',
        'express_company',
        'tracking_no',
        'weight',
        'shipping_fee',
        'sender_name',
        'sender_phone',
        'sender_address',
        'receiver_name',
        'receiver_phone',
        'receiver_address',
        'shipped_by',
        'shipped_at',
        'notes',
    ];

    protected $casts = [
        'delivery_date' => 'date',
        'weight' => 'decimal:3',
        'shipping_fee' => 'decimal:2',
        'shipped_at' => 'datetime',
    ];

    // 状态常量
    const STATUS_PENDING = 1;
    const STATUS_SHIPPED = 2;
    const STATUS_RECEIVED = 3;

    // 关联订单
    public function order()
    {
        return $this->belongsTo(SalesOrder::class);
    }

    // 关联仓库
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    // 关联发货人
    public function shippedBy()
    {
        return $this->belongsTo(Employee::class, 'shipped_by');
    }

    // 关联父发货单
    public function parent()
    {
        return $this->belongsTo(SalesDelivery::class, 'parent_id');
    }

    // 关联子发货单
    public function children()
    {
        return $this->hasMany(SalesDelivery::class, 'parent_id');
    }

    // 关联明细
    public function items()
    {
        return $this->hasMany(SalesDeliveryItem::class);
    }
}
