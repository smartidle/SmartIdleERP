<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseController extends Controller
{
    public function __construct(protected NotificationService $notificationService) {}

    public function index(Request $request)
    {
        $query = PurchaseOrder::with(['supplier']);
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }
        $orders = $query->orderBy('id', 'desc')->paginate(20);
        return $this->success($orders);
    }

    public function store(Request $request)
    {
        $request->validate(['supplier_id' => 'required']);
        try {
            DB::beginTransaction();
            $orderNo = 'PO' . date('Ymd') . str_pad(PurchaseOrder::count() + 1, 6, '0', STR_PAD_LEFT);
            $totalAmount = 0;
            $items = $request->input('items', []);
            foreach ($items as $item) {
                $totalAmount += (($item['quantity'] ?? 0) * ($item['price'] ?? 0));
            }
            $expectedDate = date('Y-m-d', strtotime('+7 days'));
            $order = PurchaseOrder::create([
                'order_no' => $orderNo,
                'supplier_id' => $request->supplier_id,
                'employee_id' => 1,
                'order_date' => date('Y-m-d'),
                'expected_date' => $expectedDate,
                'total_amount' => $totalAmount,
                'paid_amount' => 0,
                'status' => 1,
                'remark' => $request->input('remark', ''),
            ]);
            foreach ($items as $item) {
                PurchaseOrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'sku_id' => $item['sku_id'] ?? null,
                    'product_name' => $item['product_name'] ?? '',
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['price'] ?? 0,
                    'subtotal' => ($item['quantity'] ?? 0) * ($item['price'] ?? 0),
                ]);
            }
            DB::commit();
            return $this->success($order, 'Order created', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Failed: ' . $e->getMessage());
        }
    }

    public function show(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load(['supplier', 'items']);
        return $this->success($purchaseOrder);
    }

    public function update(Request $request, PurchaseOrder $purchaseOrder)
    {
        if ($purchaseOrder->status > 1) {
            return $this->error('Cannot update approved orders');
        }
        $purchaseOrder->update($request->only(['supplier_id', 'remark']));
        return $this->success($purchaseOrder, 'Updated');
    }

    public function destroy(PurchaseOrder $purchaseOrder)
    {
        if ($purchaseOrder->status > 1) {
            return $this->error('Cannot delete approved orders');
        }
        $purchaseOrder->items()->delete();
        $purchaseOrder->delete();
        return $this->success(null, 'Deleted');
    }

    public function approve(PurchaseOrder $purchaseOrder)
    {
        if ($purchaseOrder->status != 1) {
            return $this->error('Order cannot be approved (current status: ' . $purchaseOrder->status . ')');
        }
        $purchaseOrder->update(['status' => 2]);
        $this->notificationService->purchaseOrderStatusChanged($purchaseOrder, 'pending', 'approved');
        return $this->success($purchaseOrder, 'Approved');
    }
    
    public function receive(Request $request, PurchaseOrder $purchaseOrder)
    {
        if ($purchaseOrder->status != 2) {
            return $this->error('Order must be approved first');
        }
        $purchaseOrder->update(['status' => 4]);
        return $this->success($purchaseOrder, 'Received');
    }
}
