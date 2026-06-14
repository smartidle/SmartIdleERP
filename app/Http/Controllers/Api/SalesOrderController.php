<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Services\NotificationService;
use App\Services\SalesOrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesOrderController extends Controller
{
    public function __construct(
        protected NotificationService $notificationService,
        protected SalesOrderService $salesOrderService
    ) {}

    public function index(Request $request)
    {
        $query = SalesOrder::with(['customer', 'employee']);

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->input('customer_id'));
        }

        if ($request->has('search')) {
            $query->where('order_no', 'like', '%' . $request->input('search') . '%');
        }

        $orders = $query->orderBy('id', 'desc')->paginate(20);

        return $this->success($orders);
    }

    public function store(Request $request)
    {
        $request->validate([
            'customer_id' => 'required',
            'items' => 'required|array|min:1',
        ]);

        try {
            $data = $request->only(['customer_id', 'warehouse_id', 'order_date', 'delivery_date', 'shipping_contact', 'shipping_phone', 'shipping_address', 'shipping_fee', 'notes', 'discount_amount', 'promotion_amount', 'coupon_amount']);
            $data['items'] = $request->input('items');

            $order = $this->salesOrderService->createOrder($data, $request->user());

            return $this->success($order, 'Order created', 201);
        } catch (\Exception $e) {
            return $this->error('Failed: ' . $e->getMessage());
        }
    }

    public function show(SalesOrder $order)
    {
        $order->load(['customer', 'items.product', 'items.sku']);
        return $this->success($order);
    }

    public function update(Request $request, SalesOrder $order)
    {
        if ($order->status >= 3) {
            return $this->error('Cannot update confirmed orders');
        }

        try {
            $data = $request->only(['customer_id', 'warehouse_id', 'order_date', 'delivery_date', 'shipping_contact', 'shipping_phone', 'shipping_address', 'shipping_fee', 'notes', 'discount_amount', 'promotion_amount', 'coupon_amount']);
            if ($request->has('items')) {
                $data['items'] = $request->input('items');
            }

            $order = $this->salesOrderService->updateOrder($order, $data);

            return $this->success($order, 'Updated');
        } catch (\Exception $e) {
            return $this->error('Failed: ' . $e->getMessage());
        }
    }

    public function destroy(SalesOrder $order)
    {
        if ($order->status > 1) {
            return $this->error('Cannot delete confirmed orders');
        }
        $order->items()->delete();
        $order->delete();
        return $this->success(null, 'Deleted');
    }

    public function approve(SalesOrder $order)
    {
        if ($order->status != 1) {
            return $this->error('Order cannot be approved (current status: ' . $order->status . ')');
        }
        $oldStatus = $order->status;
        $order->update(['status' => 2]);
        $this->notificationService->orderStatusChanged($order, 'pending', 'approved');
        return $this->success($order, 'Approved');
    }

    public function cancel(SalesOrder $order)
    {
        if ($order->status >= 4) {
            return $this->error('Order cannot be cancelled');
        }
        $order->update(['status' => 6]);
        $this->notificationService->orderStatusChanged($order, (string)$order->getOriginal('status'), 'cancelled');
        return $this->success(null, 'Cancelled');
    }

    public function delivery(Request $request, SalesOrder $order)
    {
        if ($order->status != 2) {
            return $this->error('Order must be approved first');
        }
        $order->update(['status' => 4]);
        return $this->success($order, 'Delivered');
    }
}
