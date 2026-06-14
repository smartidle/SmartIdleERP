<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\PurchaseReceive;
use App\Models\PurchaseReceiveItem;
use App\Models\InventoryLog;
use App\Services\InventoryService;
use Illuminate\Http\Request;

class PurchaseReturnController extends Controller
{
    /**
     * 采购退货单列表
     */
    public function index(Request $request)
    {
        $query = PurchaseReturn::with(['receive', 'order', 'supplier', 'employee']);

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->has('supplier_id')) {
            $query->where('supplier_id', $request->input('supplier_id'));
        }
        if ($request->has('search')) {
            $query->where('return_no', 'like', "%{$request->input('search')}%");
        }

        $returns = $query->orderBy('id', 'desc')->paginate($request->input('per_page', 20));
        return $this->success($returns);
    }

    /**
     * 创建采购退货单（通常由收货时不合格品自动触发）
     */
    public function store(Request $request)
    {
        $request->validate([
            'receive_id' => 'required|exists:purchase_receives,id',
            'return_no' => 'required|string|max:32',
            'reason' => 'required|string|max:255',
            'items' => 'required|array',
            'items.*.receive_item_id' => 'required|exists:purchase_receive_items,id',
            'items.*.quantity' => 'required|numeric|min:0.001',
            'items.*.defective_qty' => 'nullable|numeric|min:0',
            'items.*.defect_reason' => 'nullable|string|max:255',
        ]);

        $receive = PurchaseReceive::find($request->input('receive_id'));

        $return = PurchaseReturn::create([
            'return_no' => $request->input('return_no'),
            'receive_id' => $receive->id,
            'order_id' => $receive->order_id,
            'supplier_id' => $receive->supplier_id,
            'reason' => $request->input('reason'),
            'total_amount' => 0,
            'status' => PurchaseReturn::STATUS_PENDING,
            'remark' => $request->input('remark'),
            'employee_id' => $request->user()->id,
        ]);

        $totalAmount = 0;
        foreach ($request->input('items') as $item) {
            $receiveItem = PurchaseReceiveItem::find($item['receive_item_id']);
            $defectiveQty = $item['defective_qty'] ?? $item['quantity'];
            $amount = $receiveItem->unit_price * $defectiveQty;

            PurchaseReturnItem::create([
                'return_id' => $return->id,
                'receive_item_id' => $item['receive_item_id'],
                'product_id' => $receiveItem->product_id,
                'sku_id' => $receiveItem->sku_id,
                'quantity' => $item['quantity'],
                'defective_qty' => $defectiveQty,
                'qualified_qty' => max(0, $item['quantity'] - $defectiveQty),
                'unit_price' => $receiveItem->unit_price,
                'amount' => $amount,
                'defect_reason' => $item['defect_reason'] ?? null,
                'remark' => $item['remark'] ?? null,
            ]);

            $totalAmount += $amount;
        }

        $return->update(['total_amount' => $totalAmount]);
        $return->load('items');

        return $this->success($return, 'Purchase return created', 201);
    }

    /**
     * 查看退货单详情
     */
    public function show(PurchaseReturn $return)
    {
        $return->load(['receive', 'order', 'supplier', 'employee', 'items.product', 'items.sku']);
        return $this->success($return);
    }

    /**
     * 审核退货单
     */
    public function approve(Request $request, PurchaseReturn $return)
    {
        $request->validate([
            'action' => 'required|in:approve,reject',
            'remark' => 'nullable|string|max:255',
        ]);

        if ($return->status !== PurchaseReturn::STATUS_PENDING) {
            return $this->error('Return already processed', 400);
        }

        if ($request->input('action') === 'approve') {
            $return->update([
                'status' => PurchaseReturn::STATUS_APPROVED,
                'approver_id' => $request->user()->id,
                'approved_at' => now(),
                'remark' => $request->input('remark', $return->remark),
            ]);
            $message = 'Return approved';
        } else {
            $return->update([
                'status' => PurchaseReturn::STATUS_REJECTED,
                'approver_id' => $request->user()->id,
                'approved_at' => now(),
                'remark' => $request->input('remark', $return->remark),
            ]);
            $message = 'Return rejected';
        }

        return $this->success(null, $message);
    }

    /**
     * 确认退货入库（从供应商取回货物）
     */
    public function receive(Request $request, PurchaseReturn $return)
    {
        $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'remark' => 'nullable|string',
        ]);

        if ($return->status !== PurchaseReturn::STATUS_APPROVED) {
            return $this->error('Return must be approved before receiving', 400);
        }

        $return->update([
            'status' => PurchaseReturn::STATUS_RETURNED,
            'receiver_id' => $request->user()->id,
            'received_at' => now(),
            'remark' => $request->input('remark', $return->remark),
        ]);

        // 采购退货入库 → 增加实际库存（不合格品不入库）
        $inventoryService = app(InventoryService::class);
        foreach ($return->items as $item) {
            $qualifiedQty = $item->qualified_qty ?? 0;
            if ($qualifiedQty > 0) {
                $inventoryService->addStock(
                    $item->sku_id,
                    $request->input('warehouse_id'),
                    $qualifiedQty,
                    $item->unit_price,
                    'purchase_return',
                    $return->id,
                    $request->user()->id,
                    $return->order_id,
                    InventoryLog::TYPE_RETURN_IN
                );
            }
        }

        return $this->success(null, 'Purchase return received');
    }
}