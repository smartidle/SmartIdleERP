<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StockTransfer;
use App\Models\InventoryFreeze;
use App\Services\InventoryService;
use Illuminate\Http\Request;

class StockTransferController extends Controller
{
    public function index(Request $request)
    {
        $query = StockTransfer::with([
            'fromWarehouse:id,code,name', 'toWarehouse:id,code,name',
            'items.product:id,name', 'items.sku:id,sku_code'
        ]);

        if ($request->has('search')) {
            $q = $request->input('search');
            $query->where('transfer_no', 'like', "%{$q}%");
        }
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        $list = $query->orderBy('id', 'desc')->paginate($request->input('per_page', 20));
        return $this->success($list);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'from_warehouse_id' => 'required|exists:warehouses,id|different:to_warehouse_id',
            'to_warehouse_id' => 'required|exists:warehouses,id',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.sku_id' => 'nullable|exists:product_skus,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
        ]);

        $validated['transfer_no'] = 'ST' . date('YmdHis') . str_pad(random_int(0, 99), 2, '0', STR_PAD_LEFT);
        $validated['status'] = StockTransfer::STATUS_DRAFT;
        $validated['employee_id'] = $request->user()->employee_id ?? null;

        $transfer = StockTransfer::create(collect($validated)->except('items')->toArray());

        foreach ($validated['items'] as $item) {
            // Auto-resolve sku_id if not provided
            if (empty($item['sku_id'])) {
                $sku = \App\Models\ProductSku::where('product_id', $item['product_id'])->first();
                $item['sku_id'] = $sku ? $sku->id : 0;
            }
            $transfer->items()->create($item);
        }

        $transfer->load(['fromWarehouse', 'toWarehouse', 'items.product']);
        return $this->success($transfer, '调拨单创建成功', 201);
    }

    public function show(StockTransfer $stockTransfer)
    {
        $stockTransfer->load(['fromWarehouse', 'toWarehouse', 'items.product', 'items.sku']);
        return $this->success($stockTransfer);
    }

    /**
     * 审核并发起调拨 - 从源仓库扣减库存
     */
    public function ship(Request $request, StockTransfer $stockTransfer)
    {
        if ($stockTransfer->status != StockTransfer::STATUS_DRAFT) {
            return $this->error('只有草稿状态的调拨单才能发起', 400);
        }

        $inventoryService = app(InventoryService::class);

        foreach ($stockTransfer->items as $item) {
            $inventoryService->deductStock(
                $item->sku_id ?? 0,
                $stockTransfer->from_warehouse_id,
                $item->quantity,
                0,
                'stock_transfer',
                $stockTransfer->id,
                $request->user()->employee_id ?? 0
            );
            $item->update(['transferred_qty' => $item->quantity]);
        }

        $stockTransfer->update([
            'status' => StockTransfer::STATUS_IN_TRANSIT,
            'approver_id' => $request->user()->employee_id ?? null,
            'approved_at' => now(),
        ]);

        return $this->success($stockTransfer->fresh(), '调拨已发起，库存已从源仓库扣减');
    }

    /**
     * 确认收货 - 库存入库到目标仓库
     */
    public function complete(Request $request, StockTransfer $stockTransfer)
    {
        if ($stockTransfer->status != StockTransfer::STATUS_IN_TRANSIT) {
            return $this->error('只有运输中的调拨单才能确认收货', 400);
        }

        $inventoryService = app(InventoryService::class);

        foreach ($stockTransfer->items as $item) {
            $inventoryService->addStock(
                $item->sku_id ?? 0,
                $stockTransfer->to_warehouse_id,
                $item->quantity,
                0,
                'stock_transfer',
                $stockTransfer->id,
                $request->user()->employee_id ?? 0,
                0,
                \App\Models\InventoryLog::TYPE_TRANSFER_IN
            );
        }

        $stockTransfer->update(['status' => StockTransfer::STATUS_COMPLETED]);
        return $this->success($stockTransfer->fresh(), '调拨已完成，库存已入库');
    }

    public function cancel(Request $request, StockTransfer $stockTransfer)
    {
        if ($stockTransfer->status >= StockTransfer::STATUS_COMPLETED) {
            return $this->error('已完成的调拨单不可取消', 400);
        }

        // 如果是运输中，需要退回源仓库库存
        if ($stockTransfer->status == StockTransfer::STATUS_IN_TRANSIT) {
            $inventoryService = app(InventoryService::class);
            foreach ($stockTransfer->items as $item) {
                $inventoryService->addStock(
                    $item->sku_id ?? 0,
                    $stockTransfer->from_warehouse_id,
                    $item->quantity,
                    0,
                    'stock_transfer_cancel',
                    $stockTransfer->id,
                    $request->user()->employee_id ?? 0,
                    0,
                    \App\Models\InventoryLog::TYPE_TRANSFER_IN
                );
            }
        }

        $stockTransfer->update(['status' => StockTransfer::STATUS_CANCELLED]);
        return $this->success($stockTransfer->fresh(), '调拨单已取消');
    }

    /**
     * 库存冻结记录
     */
    public function freezes(Request $request)
    {
        $query = InventoryFreeze::with(['product:id,name', 'sku:id,sku_code', 'warehouse:id,name']);

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        $list = $query->orderBy('id', 'desc')->paginate($request->input('per_page', 20));
        return $this->success($list);
    }

    /**
     * 解冻库存
     */
    public function unfreeze(Request $request, InventoryFreeze $freeze)
    {
        if ($freeze->status != InventoryFreeze::STATUS_FROZEN) {
            return $this->error('该冻结记录已解冻', 400);
        }

        $freeze->update([
            'status' => InventoryFreeze::STATUS_UNFROZEN,
            'unfreeze_by' => $request->user()->employee_id ?? null,
            'unfreeze_at' => now(),
        ]);

        return $this->success($freeze->fresh(), '库存已解冻');
    }
}
