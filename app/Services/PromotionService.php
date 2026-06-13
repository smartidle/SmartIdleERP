<?php

namespace App\Services;

use App\Models\Promotion;
use App\Models\Coupon;
use App\Models\CustomerCoupon;
use App\Models\OrderPromotion;
use App\Models\Customer;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class PromotionService
{
    /**
     * 计算订单适用的促销活动优惠
     */
    public function calculatePromotions($orderData, $customer)
    {
        $subtotal = $orderData['subtotal'] ?? 0;
        $items = $orderData['items'] ?? [];
        $productIds = array_column($items, 'product_id');

        // 获取适用的促销活动
        $promotions = Promotion::where('status', 1)
            ->where('trigger_type', Promotion::TRIGGER_AUTO)
            ->where('start_time', '<=', now())
            ->where('end_time', '>=', now())
            ->get();

        $applicablePromotions = [];

        foreach ($promotions as $promotion) {
            // 检查产品适用性
            if (!$this->isApplicableToProducts($promotion, $productIds)) {
                continue;
            }

            // 检查客户适用性
            if (!$this->isApplicableToCustomer($promotion, $customer)) {
                continue;
            }

            // 检查触发条件
            $condition = $promotion->condition_json ?? [];
            if (!$this->checkCondition($condition, $subtotal, count($items))) {
                continue;
            }

            // 计算优惠金额
            $discount = $this->calculateDiscount($promotion, $subtotal, $items);
            if ($discount > 0) {
                $applicablePromotions[] = [
                    'promotion' => $promotion,
                    'discount' => $discount,
                ];
            }
        }

        // 按优先级排序，返回最优的促销
        if (!empty($applicablePromotions)) {
            usort($applicablePromotions, function ($a, $b) {
                return $b['discount'] - $a['discount'];
            });
            return $applicablePromotions[0];
        }

        return null;
    }

    /**
     * 计算优惠券优惠
     */
    public function calculateCoupon($couponCode, $orderData, $customer)
    {
        $customerCoupon = CustomerCoupon::where('code', $couponCode)
            ->where('customer_id', $customer->id)
            ->where('status', CustomerCoupon::STATUS_UNUSED)
            ->first();

        if (!$customerCoupon) {
            return null;
        }

        $coupon = $customerCoupon->coupon;
        if (!$coupon || !$coupon->isActive() || !$coupon->hasStock()) {
            return null;
        }

        // 检查每人限领
        $usedCount = CustomerCoupon::where('coupon_id', $coupon->id)
            ->where('customer_id', $customer->id)
            ->count();
        if ($usedCount >= $coupon->per_customer_limit) {
            return null;
        }

        $subtotal = $orderData['subtotal'] ?? 0;
        $discount = $coupon->calculateDiscount($subtotal);

        if ($discount > 0) {
            return [
                'coupon' => $coupon,
                'customer_coupon' => $customerCoupon,
                'discount' => $discount,
            ];
        }

        return null;
    }

    /**
     * 应用促销到订单
     */
    public function applyPromotionToOrder($order, $promotionData, $discountAmount)
    {
        return OrderPromotion::create([
            'order_id' => $order->id,
            'promotion_id' => $promotionData['promotion']->id ?? null,
            'coupon_id' => $promotionData['coupon']->id ?? null,
            'customer_coupon_id' => $promotionData['customer_coupon']->id ?? null,
            'promotion_name' => $promotionData['promotion']->name ?? $promotionData['coupon']->name ?? '优惠券',
            'discount_amount' => $discountAmount,
            'description' => $this->generatePromotionDescription($promotionData),
        ]);
    }

    /**
     * 标记优惠券已使用
     */
    public function markCouponUsed($customerCouponId, $orderId)
    {
        $customerCoupon = CustomerCoupon::find($customerCouponId);
        if ($customerCoupon) {
            $customerCoupon->use($orderId);

            // 更新优惠券已使用数量
            $coupon = $customerCoupon->coupon;
            if ($coupon) {
                $coupon->used_quantity += 1;
                $coupon->save();
            }
        }
    }

    /**
     * 检查促销活动是否适用于指定产品
     */
    protected function isApplicableToProducts($promotion, $productIds)
    {
        $applicableProducts = $promotion->applicable_products ?? [];

        // 如果没有限制，适用于全部
        if (empty($applicableProducts) || $applicableProducts === ['all'] || $applicableProducts === 'all') {
            return true;
        }

        // 检查是否有交集
        return !empty(array_intersect($applicableProducts, $productIds));
    }

    /**
     * 检查促销活动是否适用于指定客户
     */
    protected function isApplicableToCustomer($promotion, $customer)
    {
        if (!$customer) {
            return false;
        }

        $applicableCustomers = $promotion->applicable_customers ?? [];

        // 如果没有限制，适用于全部
        if (empty($applicableCustomers) || $applicableCustomers === ['all'] || $applicableCustomers === 'all') {
            return true;
        }

        // 检查客户等级
        if (in_array('level_' . $customer->level, $applicableCustomers)) {
            return true;
        }

        // 检查客户ID
        if (in_array($customer->id, $applicableCustomers)) {
            return true;
        }

        return false;
    }

    /**
     * 检查触发条件
     */
    protected function checkCondition($condition, $subtotal, $itemCount)
    {
        if (empty($condition)) {
            return true;
        }

        $type = $condition['type'] ?? 'amount';
        $threshold = $condition['threshold'] ?? 0;
        $compare = $condition['compare'] ?? '>=';

        switch ($type) {
            case 'amount':
                return $this->compareValue($subtotal, $threshold, $compare);
            case 'quantity':
                return $this->compareValue($itemCount, $threshold, $compare);
            default:
                return true;
        }
    }

    /**
     * 比较数值
     */
    protected function compareValue($value, $threshold, $compare)
    {
        switch ($compare) {
            case '>=':
                return $value >= $threshold;
            case '>':
                return $value > $threshold;
            case '<=':
                return $value <= $threshold;
            case '<':
                return $value < $threshold;
            case '=':
                return $value == $threshold;
            default:
                return false;
        }
    }

    /**
     * 计算促销优惠金额
     */
    protected function calculateDiscount($promotion, $subtotal, $items)
    {
        $reward = $promotion->reward_json ?? [];

        switch ($promotion->type) {
            case Promotion::TYPE_FULL_REDUCE:
                // 满减
                return $reward['value'] ?? 0;

            case Promotion::TYPE_DISCOUNT:
                // 打折
                $rate = $reward['value'] ?? 1;
                $maxAmount = $reward['max_amount'] ?? 0;
                $discount = $subtotal * (1 - $rate);
                return $maxAmount > 0 ? min($discount, $maxAmount) : $discount;

            case Promotion::TYPE_FIXED_PRICE:
                // 一口价
                $fixedPrice = $reward['price'] ?? 0;
                return $subtotal - $fixedPrice;

            case Promotion::TYPE_FULL_GIFT:
                // 满赠（返回礼品信息，不直接减金额）
                return 0;

            case Promotion::TYPE_BUY_N_GET_M:
                // 买N送M（返回礼品信息，不直接减金额）
                return 0;

            default:
                return 0;
        }
    }

    /**
     * 生成促销描述
     */
    protected function generatePromotionDescription($promotionData)
    {
        if (isset($promotionData['promotion'])) {
            $promotion = $promotionData['promotion'];
            $reward = $promotion->reward_json ?? [];

            switch ($promotion->type) {
                case Promotion::TYPE_FULL_REDUCE:
                    return "满减优惠: 减" . $reward['value'] . "元";
                case Promotion::TYPE_DISCOUNT:
                    return "折扣优惠: " . ($reward['value'] * 10) . "折";
                case Promotion::TYPE_FIXED_PRICE:
                    return "一口价: " . $reward['price'] . "元";
            }
        }

        if (isset($promotionData['coupon'])) {
            return "优惠券: {$promotionData['coupon']->name}";
        }

        return '';
    }
}
