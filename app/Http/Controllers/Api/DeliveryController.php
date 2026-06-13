<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SalesDelivery;
use App\Models\SalesDeliveryItem;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\Warehouse;
use Illuminate\Http\Request;

class DeliveryController extends Controller
{
    /**
     * 发货单列表
     */
    public function index(Request $request)
    {
        $query = SalesDelivery::with(['order', 'warehouse']);

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('order_id')) {
            $query->where('order_id', $request->input('order_id'));
        }

        if ($request->has('search')) {
            $query->where('delivery_no', 'like', "%{$request->input('search')}%");
        }

        $deliveries = $query->orderBy('id', 'desc')
            ->paginate($request->input('per_page', 20));

        return $this->success($deliveries);
    }

    /**
     * 创建发货单
     */
    public function store(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:sales_orders,id',
            'delivery_no' => 'required|string|max:32',
            'warehouse_id' => 'required|exists:warehouses,id',
            'delivery_date' => 'required|date',
            'items' => 'required|array',
            'items.*.order_item_id' => 'required|exists:sales_order_items,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
        ]);

        $order = SalesOrder::find($request->input('order_id'));
        
        if ($order->status < 2) {
            return $this->error('Order must be approved before delivery', 400);
        }

        // 计算是否完全发货
        $totalDelivered = $request->input('items');
        $isComplete = $this->checkIfCompleteDelivery($order, $totalDelivered);

        $delivery = SalesDelivery::create([
            'delivery_no' => $request->input('delivery_no'),
            'order_id' => $order->id,
            'warehouse_id' => $request->input('warehouse_id'),
            'delivery_date' => $request->input('delivery_date'),
            'status' => 2, // 已发货
            'remark' => $request->input('remark'),
            'employee_id' => $request->user()->id,
        ]);

        // 创建发货明细
        foreach ($request->input('items') as $item) {
            $orderItem = SalesOrderItem::find($item['order_item_id']);
            
            SalesDeliveryItem::create([
                'delivery_id' => $delivery->id,
                'order_item_id' => $item['order_item_id'],
                'product_id' => $orderItem->product_id,
                'sku_id' => $orderItem->sku_id,
                'quantity' => $item['quantity'],
                'remark' => $item['remark'] ?? null,
            ]);

            // 更新订单项已发货数量
            $orderItem->delivered_qty += $item['quantity'];
            $orderItem->save();
        }

        // 更新订单状态
        if ($isComplete) {
            $order->status = 4; // 已发货
        } else {
            $order->status = 3; // 部分发货
        }
        $order->save();

        $delivery->load('items');

        return $this->success($delivery, 'Delivery created', 201);
    }

    /**
     * 查看发货单详情
     */
    public function show(SalesDelivery $delivery)
    {
        $delivery->load(['order', 'warehouse', 'items.product', 'items.sku']);
        return $this->success($delivery);
    }

    /**
     * 确认收货（客户确认）
     */
    public function confirm(SalesDelivery $delivery)
    {
        if ($delivery->status !== 2) {
            return $this->error('Delivery already processed', 400);
        }

        $delivery->update(['status' => 3]); // 已收货

        // 检查订单是否全部收货
        $order = $delivery->order;
        $allDelivered = $this->checkOrderFullyDelivered($order);
        
        if ($allDelivered) {
            $order->status = 5; // 已完成
            $order->save();
        }

        return $this->success(null, 'Delivery confirmed');
    }

    private function checkIfCompleteDelivery($order, $items)
    {
        foreach ($items as $item) {
            $orderItem = SalesOrderItem::find($item['order_item_id']);
            $totalNeeded = $orderItem->quantity - $orderItem->delivered_qty;
            if ($item['quantity'] < $totalNeeded) {
                return false;
            }
        }
        return true;
    }

    private function checkOrderFullyDelivered($order)
    {
        foreach ($order->items as $item) {
            if ($item->delivered_qty < $item->quantity) {
                return false;
            }
        }
        return true;
    }
}
