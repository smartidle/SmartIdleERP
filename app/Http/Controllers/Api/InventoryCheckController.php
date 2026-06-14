<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventoryCheck;
use App\Models\InventoryCheckItem;
use App\Models\Inventory;
use App\Models\InventoryLog;
use App\Models\ProductSku;
use App\Services\InventoryService;
use Illuminate\Http\Request;

class InventoryCheckController extends Controller
{
    /**
     * 盘点单列表
     */
    public function index(Request $request)
    {
        $query = InventoryCheck::with(['warehouse', 'employee', 'items']);

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->input('warehouse_id'));
        }
        if ($request->has('search')) {
            $query->where('check_no', 'like', "%{$request->input('search')}%");
        }

        $checks = $query->orderBy('id', 'desc')->paginate($request->input('per_page', 20));
        return $this->success($checks);
    }

    /**
     * 创建盘点单（自动加载系统库存）
     */
    public function store(Request $request)
    {
        $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'check_date' => 'required|date',
            'type' => 'nullable|in:1,2',
            'remark' => 'nullable|string',
            'sku_ids' => 'nullable|array', // 指定SKU（抽盘），为空则全盘
            'sku_ids.*' => 'exists:product_skus,id',
        ]);

        $checkNo = 'CHK' . date('Ymd') . str_pad(InventoryCheck::count() + 1, 6, '0', STR_PAD_LEFT);
        $warehouseId = $request->input('warehouse_id');
        $skuIds = $request->input('sku_ids', []);

        $check = InventoryCheck::create([
            'check_no' => $checkNo,
            'warehouse_id' => $warehouseId,
            'check_date' => $request->input('check_date'),
            'type' => $request->input('type', 1),
            'status' => InventoryCheck::STATUS_IN_PROGRESS,
            'remark' => $request->input('remark'),
            'employee_id' => $request->user()->id,
        ]);

        // 获取 SKU 库存（全盘或指定）
        if (empty($skuIds)) {
            // 全盘：该仓库所有 SKU
            $inventories = Inventory::where('warehouse_id', $warehouseId)
                ->where('quantity', '>', 0)
                ->get();
        } else {
            // 抽盘：指定 SKU
            $inventories = Inventory::where('warehouse_id', $warehouseId)
                ->whereIn('sku_id', $skuIds)
                ->get();
        }

        foreach ($inventories as $inv) {
            $sku = ProductSku::find($inv->sku_id);
            InventoryCheckItem::create([
                'check_id' => $check->id,
                'product_id' => $inv->product_id,
                'sku_id' => $inv->sku_id,
                'system_qty' => $inv->quantity,
                'actual_qty' => 0, // 待录入
                'difference' => 0,
                'unit_cost' => $inv->cost_price ?? 0,
                'difference_amount' => 0,
                'status' => InventoryCheckItem::STATUS_PENDING,
            ]);
        }

        $check->load('items.product', 'items.sku');
        return $this->success($check, 'Inventory check created', 201);
    }

    /**
     * 查看盘点单
     */
    public function show(InventoryCheck $check)
    {
        $check->load(['warehouse', 'employee', 'items.product', 'items.sku']);
        return $this->success($check);
    }

    /**
     * 录入实际库存（批量更新盘点明细）
     */
    public function updateItems(Request $request, InventoryCheck $check)
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.item_id' => 'required|exists:inventory_check_items,id',
            'items.*.actual_qty' => 'required|numeric|min:0',
            'items.*.reason' => 'nullable|string|max:255',
        ]);

        $items = $request->input('items');
        foreach ($items as $item) {
            $checkItem = InventoryCheckItem::find($item['item_id']);
            if (!$checkItem || $checkItem->check_id !== $check->id) {
                continue;
            }

            $diff = $item['actual_qty'] - $checkItem->system_qty;
            $diffAmount = $diff * ($checkItem->unit_cost ?? 0);

            $status = InventoryCheckItem::STATUS_PENDING;
            if ($diff > 0) $status = InventoryCheckItem::STATUS_PROFIT;
            elseif ($diff < 0) $status = InventoryCheckItem::STATUS_LOSS;

            $checkItem->update([
                'actual_qty' => $item['actual_qty'],
                'difference' => $diff,
                'difference_amount' => $diffAmount,
                'status' => $status,
                'reason' => $item['reason'] ?? null,
            ]);
        }

        // 全部录入后，更新盘点单状态为待审核
        $allEntered = $check->items()->where('actual_qty', 0)->count() === 0;
        if ($allEntered) {
            $check->update(['status' => InventoryCheck::STATUS_PENDING]);
        }

        $check->load('items.product', 'items.sku');
        return $this->success($check, 'Actual quantities updated');
    }

    /**
     * 审核盘点单（审批后执行库存调整）
     */
    public function approve(Request $request, InventoryCheck $check)
    {
        $request->validate([
            'action' => 'required|in:approve,reject',
            'remark' => 'nullable|string',
        ]);

        if ($check->status !== InventoryCheck::STATUS_PENDING) {
            return $this->error('Check must be in pending status', 400);
        }

        if ($request->input('action') === 'reject') {
            $check->update([
                'status' => InventoryCheck::STATUS_REJECTED,
                'approver_id' => $request->user()->id,
                'approved_at' => now(),
            ]);
            return $this->success(null, 'Check rejected');
        }

        // 审批通过 → 执行库存调整
        $inventoryService = app(InventoryService::class);
        foreach ($check->items as $item) {
            if ($item->difference == 0 || $item->status === InventoryCheckItem::STATUS_ADJUSTED) {
                continue;
            }

            if ($item->difference > 0) {
                // 盘盈 → 增加库存
                $inventoryService->addStock(
                    $item->sku_id,
                    $check->warehouse_id,
                    $item->difference,
                    $item->unit_cost,
                    'inventory_check',
                    $check->id,
                    $request->user()->id,
                    0,
                    InventoryLog::TYPE_CHECK_PROFIT
                );
            } else {
                // 盘亏 → 扣减库存
                $inventoryService->deductStock(
                    $item->sku_id,
                    $check->warehouse_id,
                    abs($item->difference),
                    $item->unit_cost,
                    'inventory_check',
                    $check->id,
                    $request->user()->id
                );
            }

            $item->update(['status' => InventoryCheckItem::STATUS_ADJUSTED]);
        }

        $check->update([
            'status' => InventoryCheck::STATUS_APPROVED,
            'approver_id' => $request->user()->id,
            'approved_at' => now(),
            'remark' => $request->input('remark', $check->remark),
        ]);

        return $this->success(null, 'Check approved and inventory adjusted');
    }
}