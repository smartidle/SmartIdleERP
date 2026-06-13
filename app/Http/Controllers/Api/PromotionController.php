<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Promotion;
use App\Models\PromotionProduct;
use App\Models\Coupon;
use App\Models\CouponCustomer;
use Illuminate\Http\Request;

class PromotionController extends Controller
{
    /**
     * 促销活动列表
     */
    public function index(Request $request)
    {
        $query = Promotion::query();

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->has('search')) {
            $query->where('name', 'like', "%{$request->input('search')}%");
        }

        $promotions = $query->orderBy('id', 'desc')
            ->paginate($request->input('per_page', 20));

        return $this->success($promotions);
    }

    /**
     * 创建促销活动
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:128',
            'type' => 'required|in:1,2,3,4',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        $promotion = Promotion::create([
            'name' => $request->input('name'),
            'type' => $request->input('type'),
            'discount_type' => $request->input('discount_type', 1),
            'discount_value' => $request->input('discount_value', 0),
            'min_amount' => $request->input('min_amount', 0),
            'max_discount' => $request->input('max_discount', 0),
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'status' => $request->input('status', 1),
            'description' => $request->input('description'),
        ]);

        return $this->success($promotion, 'Promotion created', 201);
    }

    /**
     * 更新促销活动
     */
    public function update(Request $request, Promotion $promotion)
    {
        $request->validate([
            'name' => 'sometimes|string|max:128',
            'type' => 'sometimes|in:1,2,3,4',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date',
        ]);

        $promotion->update($request->only([
            'name', 'type', 'discount_type', 'discount_value',
            'min_amount', 'max_discount', 'start_date', 'end_date', 'status', 'description'
        ]));

        return $this->success($promotion, 'Promotion updated');
    }

    /**
     * 删除促销活动
     */
    public function destroy(Promotion $promotion)
    {
        $promotion->delete();
        return $this->success(null, 'Promotion deleted');
    }

    /**
     * 优惠券列表
     */
    public function coupons(Request $request)
    {
        $query = Coupon::query();

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('type')) {
            $query->where('type', $request->input('type'));
        }

        $coupons = $query->orderBy('id', 'desc')
            ->paginate($request->input('per_page', 20));

        return $this->success($coupons);
    }

    /**
     * 创建优惠券
     */
    public function createCoupon(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:128',
            'code' => 'required|string|max:32|unique:coupons,code',
            'type' => 'required|in:1,2,3',
            'value' => 'required|numeric|min:0',
            'min_amount' => 'nullable|numeric|min:0',
            'total_count' => 'nullable|integer|min:1',
            'per_customer_limit' => 'nullable|integer|min:1',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        $coupon = Coupon::create([
            'name' => $request->input('name'),
            'code' => $request->input('code'),
            'type' => $request->input('type'),
            'value' => $request->input('value'),
            'min_amount' => $request->input('min_amount', 0),
            'total_count' => $request->input('total_count'),
            'used_count' => 0,
            'per_customer_limit' => $request->input('per_customer_limit', 1),
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'status' => $request->input('status', 1),
            'description' => $request->input('description'),
        ]);

        return $this->success($coupon, 'Coupon created', 201);
    }

    /**
     * 发放优惠券给客户
     */
    public function assignCoupon(Request $request)
    {
        $request->validate([
            'coupon_id' => 'required|exists:coupons,id',
            'customer_ids' => 'required|array',
        ]);

        $coupon = Coupon::find($request->input('coupon_id'));
        
        foreach ($request->input('customer_ids') as $customerId) {
            CouponCustomer::create([
                'coupon_id' => $coupon->id,
                'customer_id' => $customerId,
                'status' => 1,
            ]);
        }

        return $this->success(null, 'Coupon assigned successfully');
    }

    /**
     * 验证优惠券
     */
    public function validateCoupon(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'order_amount' => 'required|numeric|min:0',
        ]);

        $coupon = Coupon::where('code', $request->input('code'))
            ->where('status', 1)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->first();

        if (!$coupon) {
            return $this->error('Coupon not found or expired', 400);
        }

        if ($coupon->total_count && $coupon->used_count >= $coupon->total_count) {
            return $this->error('Coupon already fully distributed', 400);
        }

        if ($request->input('order_amount') < $coupon->min_amount) {
            return $this->error('Order amount does not meet minimum requirement', 400);
        }

        return $this->success([
            'coupon_id' => $coupon->id,
            'discount' => $this->calculateDiscount($coupon, $request->input('order_amount')),
        ]);
    }

    private function calculateDiscount($coupon, $orderAmount)
    {
        switch ($coupon->type) {
            case 1: // 满减
                return min($coupon->value, $orderAmount);
            case 2: // 百分比折扣
                $discount = $orderAmount * ($coupon->value / 100);
                return round($discount, 2);
            case 3: // 固定金额
                return min($coupon->value, $orderAmount);
            default:
                return 0;
        }
    }
}
