<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerCoupon extends Model
{
    use HasFactory;

    protected $table = 'customer_coupons';

    protected $fillable = [
        'customer_id',
        'coupon_id',
        'code',
        'status',
        'order_id',
        'used_at',
        'received_at',
        'expire_at',
    ];

    protected $casts = [
        'used_at' => 'datetime',
        'received_at' => 'datetime',
        'expire_at' => 'datetime',
    ];

    // 状态常量
    const STATUS_UNUSED = 1;
    const STATUS_USED = 2;
    const STATUS_EXPIRED = 3;

    // 关联客户
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    // 关联优惠券
    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }

    // 关联订单
    public function order()
    {
        return $this->belongsTo(SalesOrder::class);
    }

    // 检查是否可用
    public function isUsable()
    {
        if ($this->status != self::STATUS_UNUSED) {
            return false;
        }
        if ($this->expire_at && $this->expire_at < now()) {
            return false;
        }
        return true;
    }

    // 使用优惠券
    public function use($orderId)
    {
        $this->update([
            'status' => self::STATUS_USED,
            'order_id' => $orderId,
            'used_at' => now(),
        ]);
    }
}
