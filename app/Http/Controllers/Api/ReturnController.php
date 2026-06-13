<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SalesReturn;
use App\Models\SalesReturnItem;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use Illuminate\Http\Request;

class ReturnController extends Controller
{
    /**
     * 退货单列表
     */
    public function index(Request $request)
    {
        $query = SalesReturn::with(['order', 'customer']);

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->has('search')) {
            $query->where('return_no', 'like', "%{$request->input('search')}%");
        }

        $returns = $query->orderBy('id', 'desc')
            ->paginate($request->input('per_page', 20));

        return $this->success($returns);
    }

    /**
     * 创建退货单
     */
    public function store(Request $request)
    {
        $request->validate([
            'return_no' => 'required|string|max:32',
            'order_id' => 'required|exists:sales_orders,id',
            'type' => 'required|in:1,2', // 1=退货 2=换货
            'reason' => 'required|string|max:255',
            'items' => 'required|array',
            'items.*.order_item_id' => 'required|exists:sales_order_items,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.reason' => 'nullable|string|max:255',
        ]);

        $order = SalesOrder::find($request->input('order_id'));

        $return = SalesReturn::create([
            'return_no' => $request->input('return_no'),
            'order_id' => $order->id,
            'customer_id' => $order->customer_id,
            'type' => $request->input('type'),
            'reason' => $request->input('reason'),
            'total_amount' => 0,
            'status' => 1, // 待审核
            'remark' => $request->input('remark'),
            'employee_id' => $request->user()->id,
        ]);

        // 创建退货明细
        $totalAmount = 0;
        foreach ($request->input('items') as $item) {
            $orderItem = SalesOrderItem::find($item['order_item_id']);
            $amount = $orderItem->unit_price * $item['quantity'];

            SalesReturnItem::create([
                'return_id' => $return->id,
                'order_item_id' => $item['order_item_id'],
                'product_id' => $orderItem->product_id,
                'sku_id' => $orderItem->sku_id,
                'quantity' => $item['quantity'],
                'price' => $orderItem->unit_price,
                'amount' => $amount,
                'reason' => $item['reason'] ?? null,
            ]);

            $totalAmount += $amount;
        }

        $return->update(['total_amount' => $totalAmount]);

        return $this->success($return, 'Return request created', 201);
    }

    /**
     * 查看退货详情
     */
    public function show(SalesReturn $return)
    {
        $return->load(['order', 'customer', 'items.product', 'items.sku']);
        return $this->success($return);
    }

    /**
     * 审核退货
     */
    public function approve(Request $request, SalesReturn $return)
    {
        $request->validate([
            'action' => 'required|in:approve,reject',
            'remark' => 'nullable|string|max:255',
        ]);

        if ($return->status !== 1) {
            return $this->error('Return already processed', 400);
        }

        if ($request->input('action') === 'approve') {
            $return->update([
                'status' => 2, // 已审核，待入库
                'approver_id' => $request->user()->id,
                'approved_at' => now(),
                'approver_remark' => $request->input('remark'),
            ]);
            $message = 'Return approved';
        } else {
            $return->update([
                'status' => 4, // 已拒绝
                'approver_id' => $request->user()->id,
                'approved_at' => now(),
                'approver_remark' => $request->input('remark'),
            ]);
            $message = 'Return rejected';
        }

        return $this->success(null, $message);
    }

    /**
     * 确认退货入库
     */
    public function receive(Request $request, SalesReturn $return)
    {
        if ($return->status !== 2) {
            return $this->error('Return must be approved before receiving', 400);
        }

        $return->update([
            'status' => 3, // 已入库
            'received_at' => now(),
            'receiver_id' => $request->user()->id,
        ]);

        // 退货入库逻辑 - 更新订单已退货数量
        foreach ($return->items as $item) {
            $orderItem = SalesOrderItem::find($item->order_item_id);
            if ($orderItem) {
                $orderItem->delivered_qty -= $item->quantity;
                $orderItem->save();
            }
        }

        // 退款处理（如果是退货而非换货）
        if ($return->type === 1) {
            // 更新订单退款金额
            $order = $return->order;
            $order->refunded_amount = ($order->refunded_amount ?? 0) + $return->total_amount;
            $order->save();
        }

        return $this->success(null, 'Return received');
    }

    /**
     * 换货发货（换货场景）
     */
    public function exchange(Request $request, SalesReturn $return)
    {
        if ($return->type !== 2) {
            return $this->error('Only exchange returns can be shipped', 400);
        }

        if ($return->status !== 2) {
            return $this->error('Return must be approved', 400);
        }

        $request->validate([
            'exchange_delivery_no' => 'required|string|max:32',
            'warehouse_id' => 'required|exists:warehouses,id',
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.sku_id' => 'nullable|exists:product_skus,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
        ]);

        // 创建换货发货单
        // 简化处理：直接更新退货单状态
        $return->update([
            'status' => 5, // 已换货
            'exchange_delivery_no' => $request->input('exchange_delivery_no'),
            'exchanged_at' => now(),
        ]);

        return $this->success(null, 'Exchange shipped');
    }
}
