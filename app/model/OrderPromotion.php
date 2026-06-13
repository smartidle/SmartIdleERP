<?php

namespace app\model;

use think\Model;

/**
 * 订单促销记录模型
 */
class OrderPromotion extends Model
{
    // 表名
    protected $name = 'order_promotion';

    // 自动时间戳
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';

    // 类型转换
    protected $type = [
        'order_id' => 'integer',
        'promotion_id' => 'integer',
        'coupon_id' => 'integer',
        'customer_coupon_id' => 'integer',
        'discount_amount' => 'float',
    ];

    /**
     * 订单关联
     */
    public function order()
    {
        return $this->belongsTo(SalesOrder::class, 'order_id');
    }

    /**
     * 促销活动关联
     */
    public function promotion()
    {
        return $this->belongsTo(Promotion::class, 'promotion_id');
    }

    /**
     * 优惠券关联
     */
    public function coupon()
    {
        return $this->belongsTo(Coupon::class, 'coupon_id');
    }

    /**
     * 客户优惠券关联
     */
    public function customerCoupon()
    {
        return $this->belongsTo(CustomerCoupon::class, 'customer_coupon_id');
    }
}
