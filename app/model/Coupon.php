<?php

namespace app\model;

use think\Model;

/**
 * 优惠券模型
 */
class Coupon extends Model
{
    // 表名
    protected $name = 'coupon';

    // 自动时间戳
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    // 类型转换
    protected $type = [
        'type' => 'integer',
        'value' => 'float',
        'max_discount' => 'float',
        'min_amount' => 'float',
        'total_quantity' => 'integer',
        'used_quantity' => 'integer',
        'per_customer_limit' => 'integer',
        'start_time' => 'integer',
        'end_time' => 'integer',
        'status' => 'integer',
    ];

    // 优惠券类型常量
    const TYPE_FULL_REDUCE = 1;      // 满减券
    const TYPE_DISCOUNT = 2;         // 折扣券
    const TYPE_NO_THRESHOLD = 3;    // 无门槛券

    /**
     * 适用范围获取器
     */
    public function getApplicableProductsAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * 适用范围设置器
     */
    public function setApplicableProductsAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    /**
     * 适用分类获取器
     */
    public function getApplicableCategoriesAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * 适用分类设置器
     */
    public function setApplicableCategoriesAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    /**
     * 检查优惠券是否有效
     */
    public function isValid(): bool
    {
        if ($this->status != 1) {
            return false;
        }

        $now = time();
        if ($now < $this->start_time || $now > $this->end_time) {
            return false;
        }

        if ($this->total_quantity > 0 && $this->used_quantity >= $this->total_quantity) {
            return false;
        }

        return true;
    }

    /**
     * 获取类型名称
     */
    public function getTypeName(): string
    {
        $types = [
            self::TYPE_FULL_REDUCE => '满减券',
            self::TYPE_DISCOUNT => '折扣券',
            self::TYPE_NO_THRESHOLD => '无门槛券',
        ];

        return $types[$this->type] ?? '未知';
    }

    /**
     * 计算优惠金额
     */
    public function calculateDiscount(float $orderAmount): float
    {
        if ($orderAmount < $this->min_amount) {
            return 0;
        }

        switch ($this->type) {
            case self::TYPE_FULL_REDUCE:
                $discount = $this->value;
                break;
            case self::TYPE_DISCOUNT:
                $discount = $orderAmount * (1 - $this->value / 100);
                if ($this->max_discount > 0) {
                    $discount = min($discount, $this->max_discount);
                }
                break;
            case self::TYPE_NO_THRESHOLD:
                $discount = $this->value;
                break;
            default:
                $discount = 0;
        }

        return round($discount, 2);
    }
}
