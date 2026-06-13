<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    use HasFactory;

    protected $table = 'coupons';

    protected $fillable = [
        'name',
        'type',
        'value',
        'max_discount',
        'min_amount',
        'total_quantity',
        'used_quantity',
        'per_customer_limit',
        'start_time',
        'end_time',
        'applicable_products',
        'applicable_categories',
        'applicable_channels',
        'status',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'max_discount' => 'decimal:2',
        'min_amount' => 'decimal:2',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'applicable_products' => 'array',
        'applicable_categories' => 'array',
        'applicable_channels' => 'array',
    ];

    // 类型常量
    const TYPE_FULL_REDUCE = 1;   // 满减券
    const TYPE_DISCOUNT = 2;      // 折扣券
    const TYPE_NO_THRESHOLD = 3;  // 无门槛券

    // 检查是否在有效期
    public function isActive()
    {
        $now = now();
        return $this->status == 1 && $this->start_time <= $now && $this->end_time >= $now;
    }

    // 检查是否还有库存
    public function hasStock()
    {
        return $this->used_quantity < $this->total_quantity;
    }

    // 计算优惠金额
    public function calculateDiscount($orderAmount)
    {
        if ($orderAmount < $this->min_amount) {
            return 0;
        }

        switch ($this->type) {
            case self::TYPE_FULL_REDUCE:
            case self::TYPE_NO_THRESHOLD:
                return min($this->value, $this->max_discount ?? $this->value);
            case self::TYPE_DISCOUNT:
                $discount = $orderAmount * (1 - $this->value);
                return min($discount, $this->max_discount ?? $discount);
            default:
                return 0;
        }
    }
}
