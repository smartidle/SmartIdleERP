<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderPromotion extends Model
{
    use HasFactory;

    protected $table = 'order_promotions';

    protected $fillable = [
        'order_id',
        'promotion_id',
        'coupon_id',
        'customer_coupon_id',
        'promotion_name',
        'discount_amount',
        'description',
    ];

    protected $casts = [
        'discount_amount' => 'decimal:2',
    ];

    // 关联订单
    public function order()
    {
        return $this->belongsTo(SalesOrder::class);
    }

    // 关联促销活动
    public function promotion()
    {
        return $this->belongsTo(Promotion::class);
    }

    // 关联优惠券
    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }

    // 关联客户优惠券
    public function customerCoupon()
    {
        return $this->belongsTo(CustomerCoupon::class);
    }
}
