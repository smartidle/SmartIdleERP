<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PurchaseReceive;
use App\Models\PurchaseReceiveItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseReceiveItem as PRItem;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Http\Request;

class PurchaseReceiveController extends Controller
{
    /**
     * 采购收货单列表
     */
    public function index(Request $request)
    {
        $query = PurchaseReceive::with(['order', 'warehouse']);

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('order_id')) {
            $query->where('order_id', $request->input('order_id'));
        }

        if ($request->has('search')) {
            $query->where('receive_no', 'like', "%{$request->input('search')}%");
        }

        $receives = $query->orderBy('id', 'desc')
            ->paginate($request->input('per_page', 20));

        return $this->success($receives);
    }

    /**
     * 创建采购收货单
     */
    public function store(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:purchase_orders,id',
            'receive_no' => 'required|string|max:32',
            'warehouse_id' => 'required|exists:warehouses,id',
            'receive_date' => 'required|date',
            'items' => 'required|array',
            'items.*.order_item_id' => 'required|exists:purchase_order_items,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.qualified_qty' => 'nullable|numeric|min:0',
            'items.*.defective_qty' => 'nullable|numeric|min:0',
        ]);

        $order = PurchaseOrder::find($request->input('order_id'));
        
        if ($order->status < 2) {
            return $this->error('Order must be approved before receiving', 400);
        }

        $receive = PurchaseReceive::create([
            'receive_no' => $request->input('receive_no'),
            'order_id' => $order->id,
            'supplier_id' => $order->supplier_id,
            'warehouse_id' => $request->input('warehouse_id'),
            'receive_date' => $request->input('receive_date'),
            'total_amount' => 0,
            'status' => 2, // 已入库
            'remark' => $request->input('remark'),
            'employee_id' => $request->user()->id,
        ]);

        // 创建收货明细
        $totalAmount = 0;
        $inventoryService = app(InventoryService::class);
        foreach ($request->input('items') as $item) {
            $orderItem = PurchaseOrderItem::find($item['order_item_id']);
            $qualifiedQty = $item['qualified_qty'] ?? $item['quantity'];
            $defectiveQty = $item['defective_qty'] ?? 0;
            $amount = $orderItem->unit_price * $qualifiedQty;

            PRItem::create([
                'receive_id' => $receive->id,
                'order_item_id' => $item['order_item_id'],
                'product_id' => $orderItem->product_id,
                'sku_id' => $orderItem->sku_id,
                'quantity' => $item['quantity'],
                'qualified_qty' => $qualifiedQty,
                'defective_qty' => $defectiveQty,
                'unit_price' => $orderItem->unit_price,
                'amount' => $amount,
                'remark' => $item['remark'] ?? null,
            ]);

            // 更新订单项已入库数量
            $orderItem->received_qty += $qualifiedQty;
            $orderItem->save();

            // 采购入库 → 增加实际库存
            if ($qualifiedQty > 0) {
                $inventoryService->addStock(
                    $orderItem->sku_id,
                    $request->input('warehouse_id'),
                    $qualifiedQty,
                    $orderItem->unit_price,
                    'purchase_receive',
                    $receive->id,
                    $request->user()->id,
                    $order->id,
                    \App\Models\InventoryLog::TYPE_PURCHASE_IN
                );
            }

            $totalAmount += $amount;
        }

        $receive->update(['total_amount' => $totalAmount]);

        // 检查是否完全入库
        $isComplete = $this->checkIfCompleteReceive($order);
        
        if ($isComplete) {
            $order->status = 4; // 已完成
        } else {
            $order->status = 3; // 部分入库
        }
        $order->save();

        $receive->load('items');

        return $this->success($receive, 'Purchase received', 201);
    }

    /**
     * 查看收货单详情
     */
    public function show(PurchaseReceive $receive)
    {
        $receive->load(['order', 'warehouse', 'items.product', 'items.sku']);
        return $this->success($receive);
    }

    private function checkIfCompleteReceive($order)
    {
        foreach ($order->items as $item) {
            if ($item->received_qty < $item->quantity) {
                return false;
            }
        }
        return true;
    }
}
