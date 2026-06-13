<?php

namespace app\admin\service;

use app\model\Promotion;
use app\model\Coupon;
use app\model\CustomerCoupon;
use app\model\Customer;
use think\facade\Db;

/**
 * 促销服务
 */
class PromotionService
{
    /**
     * 计算订单促销优惠
     *
     * @param int $customerId 客户ID
     * @param float $orderAmount 订单金额
     * @param array $productIds 产品ID列表
     * @param int $channel 渠道（1=PC 2=H5）
     * @return array 优惠信息
     */
    public function calculatePromotion(
        int $customerId,
        float $orderAmount,
        array $productIds = [],
        int $channel = 1
    ): array {
        $customer = Customer::find($customerId);
        $customerLevel = $customer ? $customer->level : 1;

        // 获取适用的促销活动
        $promotions = $this->getApplicablePromotions($customerId, $customerLevel, $productIds, $channel);

        if (empty($promotions)) {
            return [
                'has_promotion' => false,
                'promotion_id' => 0,
                'promotion_name' => '',
                'discount_amount' => 0,
            ];
        }

        // 按优先级排序
        usort($promotions, function ($a, $b) {
            return $b['priority'] - $a['priority'];
        });

        // 选择最优优惠
        $bestPromotion = null;
        $bestDiscount = 0;

        foreach ($promotions as $promotion) {
            $discount = $this->calculatePromotionDiscount($promotion, $orderAmount, $productIds);
            if ($discount > $bestDiscount) {
                $bestDiscount = $discount;
                $bestPromotion = $promotion;
            }
        }

        if (!$bestPromotion || $bestDiscount <= 0) {
            return [
                'has_promotion' => false,
                'promotion_id' => 0,
                'promotion_name' => '',
                'discount_amount' => 0,
            ];
        }

        return [
            'has_promotion' => true,
            'promotion_id' => $bestPromotion['id'],
            'promotion_name' => $bestPromotion['name'],
            'discount_amount' => $bestDiscount,
            'description' => $this->getPromotionDescription($bestPromotion),
        ];
    }

    /**
     * 获取适用的促销活动
     */
    protected function getApplicablePromotions(
        int $customerId,
        int $customerLevel,
        array $productIds,
        int $channel
    ): array {
        $now = time();

        $promotions = Promotion::where('status', 1)
            ->where('start_time', '<=', $now)
            ->where('end_time', '>=', $now)
            ->where(function ($query) {
                $query->whereOr([
                    ['max_usage', '=', 0],
                    ['used_count', '<', Db::raw('max_usage')],
                ]);
            })
            ->select()
            ->toArray();

        $result = [];
        foreach ($promotions as $promotion) {
            // 检查客户适用范围
            if (!$this->checkCustomerApplicable($promotion, $customerId, $customerLevel)) {
                continue;
            }

            // 检查产品适用范围
            if (!empty($productIds) && !$this->checkProductApplicable($promotion, $productIds)) {
                continue;
            }

            // 检查渠道适用范围
            if (!$this->checkChannelApplicable($promotion, $channel)) {
                continue;
            }

            // 检查触发条件
            if (!$this->checkCondition($promotion, $customerId, count($productIds))) {
                continue;
            }

            $result[] = $promotion;
        }

        return $result;
    }

    /**
     * 检查客户是否适用
     */
    protected function checkCustomerApplicable(array $promotion, int $customerId, int $customerLevel): bool
    {
        $applicableCustomers = $promotion['applicable_customers'];

        // 如果设置了"全部适用"
        if (empty($applicableCustomers) || $applicableCustomers === ['all'] || $applicableCustomers === 'all') {
            return true;
        }

        // 检查是否指定了客户等级
        $levels = is_array($applicableCustomers) ? $applicableCustomers : json_decode($applicableCustomers, true);
        if (in_array('level_' . $customerLevel, $levels) || in_array($customerId, $levels)) {
            return true;
        }

        return false;
    }

    /**
     * 检查产品是否适用
     */
    protected function checkProductApplicable(array $promotion, array $productIds): bool
    {
        $applicableProducts = $promotion['applicable_products'];

        // 如果设置了"全部适用"
        if (empty($applicableProducts) || $applicableProducts === ['all'] || $applicableProducts === 'all') {
            return true;
        }

        $applicable = is_array($applicableProducts) ? $applicableProducts : json_decode($applicableProducts, true);

        // 检查是否有交集
        return !empty(array_intersect($productIds, $applicable));
    }

    /**
     * 检查渠道是否适用
     */
    protected function checkChannelApplicable(array $promotion, int $channel): bool
    {
        $applicableChannels = $promotion['applicable_channels'];

        // 如果设置了"全部适用"
        if (empty($applicableChannels) || $applicableChannels === ['all'] || $applicableChannels === 'all') {
            return true;
        }

        $channels = is_array($applicableChannels) ? $applicableChannels : json_decode($applicableChannels, true);

        return in_array($channel, $channels);
    }

    /**
     * 检查触发条件
     */
    protected function checkCondition(array $promotion, int $customerId, int $productCount): bool
    {
        $condition = $promotion['condition_json'];
        if (empty($condition)) {
            return true;
        }

        $condition = is_array($condition) ? $condition : json_decode($condition, true);

        switch ($condition['type'] ?? 'amount') {
            case 'amount':
                // 金额条件
                // 需要外部传入订单金额，这里简化处理
                return true;

            case 'quantity':
                // 数量条件
                $threshold = $condition['threshold'] ?? 0;
                $compare = $condition['compare'] ?? '>=';
                return $this->compareValue($productCount, $threshold, $compare);

            default:
                return true;
        }
    }

    /**
     * 比较数值
     */
    protected function compareValue($value, $threshold, $compare): bool
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
            case '==':
                return $value == $threshold;
            default:
                return true;
        }
    }

    /**
     * 计算促销优惠金额
     */
    protected function calculatePromotionDiscount(array $promotion, float $orderAmount, array $productIds): float
    {
        $reward = $promotion['reward_json'];
        $reward = is_array($reward) ? $reward : json_decode($reward, true);

        if (empty($reward)) {
            return 0;
        }

        $condition = $promotion['condition_json'];
        $condition = is_array($condition) ? $condition : json_decode($condition, true);

        switch ($reward['type'] ?? '') {
            case 'discount':
                // 直接减免
                return (float) ($reward['value'] ?? 0);

            case 'discount_rate':
                // 折扣
                $discount = $orderAmount * (1 - ($reward['value'] ?? 1));
                $maxAmount = $reward['max_amount'] ?? 0;
                if ($maxAmount > 0 && $discount > $maxAmount) {
                    $discount = $maxAmount;
                }
                return $discount;

            case 'gift':
                // 赠品（这里只计算优惠金额，实际赠品需要在订单中处理）
                return 0;

            default:
                return 0;
        }
    }

    /**
     * 获取促销描述
     */
    protected function getPromotionDescription(array $promotion): string
    {
        $reward = $promotion['reward_json'];
        $reward = is_array($reward) ? $reward : json_decode($reward, true);

        if (empty($reward)) {
            return '';
        }

        switch ($reward['type'] ?? '') {
            case 'discount':
                return "满减{$reward['value']}元";
            case 'discount_rate':
                $rate = round((1 - $reward['value']) * 10, 1);
                return "打{$rate}折";
            case 'gift':
                return "送赠品";
            default:
                return '';
        }
    }

    /**
     * 使用优惠券
     *
     * @param int $customerCouponId 客户优惠券ID
     * @param float $orderAmount 订单金额
     * @return array 优惠结果
     */
    public function useCoupon(int $customerCouponId, float $orderAmount): array
    {
        $customerCoupon = CustomerCoupon::find($customerCouponId);
        if (!$customerCoupon) {
            throw new \Exception('优惠券不存在');
        }

        if (!$customerCoupon->isAvailable()) {
            throw new \Exception('优惠券不可用');
        }

        $coupon = $customerCoupon->coupon;
        if (!$coupon) {
            throw new \Exception('优惠券信息不存在');
        }

        $discount = $coupon->calculateDiscount($orderAmount);

        return [
            'customer_coupon_id' => $customerCouponId,
            'coupon_id' => $coupon->id,
            'coupon_name' => $coupon->name,
            'discount_amount' => $discount,
        ];
    }

    /**
     * 获取客户可用优惠券
     *
     * @param int $customerId 客户ID
     * @param float $orderAmount 订单金额（用于检查门槛）
     * @return array
     */
    public function getAvailableCoupons(int $customerId, float $orderAmount): array
    {
        $now = time();

        return Db::name('customer_coupon cc')
            ->join('coupon c', 'c.id = cc.coupon_id')
            ->where('cc.customer_id', $customerId)
            ->where('cc.status', CustomerCoupon::STATUS_UNUSED)
            ->where('c.status', 1)
            ->where('c.start_time', '<=', $now)
            ->where('c.end_time', '>=', $now)
            ->whereOr('c.total_quantity', 0)
            ->whereColumn('c.used_quantity', '<', 'c.total_quantity')
            ->select()
            ->toArray();
    }

    /**
     * 发放优惠券给客户
     *
     * @param int $couponId 优惠券ID
     * @param int $customerId 客户ID
     * @param string|null $code 优惠券码
     * @return CustomerCoupon
     */
    public function distributeCoupon(int $couponId, int $customerId, ?string $code = null): CustomerCoupon
    {
        $coupon = Coupon::find($couponId);
        if (!$coupon) {
            throw new \Exception('优惠券不存在');
        }

        // 检查发行量
        if ($coupon->total_quantity > 0 && $coupon->used_quantity >= $coupon->total_quantity) {
            throw new \Exception('优惠券已发完');
        }

        // 检查客户已领取数量
        $receivedCount = CustomerCoupon::where('coupon_id', $couponId)
            ->where('customer_id', $customerId)
            ->count();

        if ($receivedCount >= $coupon->per_customer_limit) {
            throw new \Exception('已达到领取上限');
        }

        return CustomerCoupon::create([
            'customer_id' => $customerId,
            'coupon_id' => $couponId,
            'code' => $code ?: $this->generateCouponCode(),
            'status' => CustomerCoupon::STATUS_UNUSED,
            'received_at' => time(),
            'expire_at' => $coupon->end_time,
        ]);
    }

    /**
     * 生成优惠券码
     */
    protected function generateCouponCode(): string
    {
        return strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 12));
    }
}
