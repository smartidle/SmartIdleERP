<?php

namespace app\model;

use think\Model;

/**
 * 客户优惠券模型
 */
class CustomerCoupon extends Model
{
    // 表名
    protected $name = 'customer_coupon';

    // 自动时间戳
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';

    // 类型转换
    protected $type = [
        'customer_id' => 'integer',
        'coupon_id' => 'integer',
        'status' => 'integer',
        'order_id' => 'integer',
        'used_at' => 'integer',
        'received_at' => 'integer',
        'expire_at' => 'integer',
    ];

    // 状态常量
    const STATUS_UNUSED = 1;     // 未使用
    const STATUS_USED = 2;      // 已使用
    const STATUS_EXPIRED = 3;   // 已过期

    /**
     * 客户关联
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /**
     * 优惠券关联
     */
    public function coupon()
    {
        return $this->belongsTo(Coupon::class, 'coupon_id');
    }

    /**
     * 订单关联
     */
    public function order()
    {
        return $this->belongsTo(SalesOrder::class, 'order_id');
    }

    /**
     * 检查是否可用
     */
    public function isAvailable(): bool
    {
        if ($this->status != self::STATUS_UNUSED) {
            return false;
        }

        if ($this->expire_at > 0 && time() > $this->expire_at) {
            return false;
        }

        $coupon = $this->coupon;
        if (!$coupon || !$coupon->isValid()) {
            return false;
        }

        return true;
    }

    /**
     * 使用优惠券
     */
    public function use(int $orderId): void
    {
        if (!$this->isAvailable()) {
            throw new \Exception('优惠券不可用');
        }

        $this->status = self::STATUS_USED;
        $this->order_id = $orderId;
        $this->used_at = time();
        $this->save();

        // 更新优惠券已使用数量
        $coupon = $this->coupon;
        if ($coupon) {
            $coupon->used_quantity++;
            $coupon->save();
        }
    }
}
