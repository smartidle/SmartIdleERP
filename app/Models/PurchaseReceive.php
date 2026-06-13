<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseReceive extends Model
{
    use HasFactory;

    protected $table = 'purchase_receives';

    protected $fillable = [
        'receive_no',
        'order_id',
        'warehouse_id',
        'receive_date',
        'status',
        'notes',
        'received_by',
    ];

    protected $casts = [
        'receive_date' => 'date',
    ];

    // 状态常量
    const STATUS_PENDING = 1;
    const STATUS_RECEIVED = 2;
    const STATUS_PARTIAL = 3;

    // 关联订单
    public function order()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    // 关联仓库
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    // 关联收货人
    public function receivedBy()
    {
        return $this->belongsTo(Employee::class, 'received_by');
    }

    // 关联明细
    public function items()
    {
        return $this->hasMany(PurchaseReceiveItem::class);
    }
}
