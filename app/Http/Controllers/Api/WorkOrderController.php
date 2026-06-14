<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WorkOrder;
use App\Models\WorkOrderOperation;
use App\Models\WorkOrderReport;
use App\Models\WorkOrderMaterial;
use App\Services\InventoryService;
use Illuminate\Http\Request;

class WorkOrderController extends Controller
{
    public function index(Request $request)
    {
        $query = WorkOrder::with(['product:id,name,spec', 'sku:id,sku_code', 'bom:id,code', 'warehouse:id,name']);

        if ($request->has('search')) {
            $q = $request->input('search');
            $query->where(function ($sq) use ($q) {
                $sq->where('wo_no', 'like', "%{$q}%")
                   ->orWhereHas('product', fn($p) => $p->where('name', 'like', "%{$q}%"));
            });
        }
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->has('priority')) {
            $query->where('priority', $request->input('priority'));
        }

        $list = $query->orderBy('priority', 'desc')->orderBy('id', 'desc')
            ->paginate($request->input('per_page', 20));
        return $this->success($list);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'bom_id' => 'required|exists:boms,id',
            'product_id' => 'required|exists:products,id',
            'sku_id' => 'nullable|exists:product_skus,id',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'planned_qty' => 'required|numeric|min:0.01',
            'priority' => 'nullable|integer|in:1,2,3',
            'work_hours' => 'nullable|numeric|min:0',
            'sales_order_id' => 'nullable|integer',
            'planned_start' => 'nullable|date',
            'planned_end' => 'nullable|date',
            'remark' => 'nullable|string',
        ]);

        // 生成工单号
        $validated['wo_no'] = 'WO' . date('YmdHis') . str_pad(random_int(0, 99), 2, '0', STR_PAD_LEFT);
        $validated['status'] = WorkOrder::STATUS_PENDING;
        $validated['employee_id'] = $request->user()->employee_id ?? null;

        $wo = WorkOrder::create($validated);

        // 从BOM自动生成领料明细
        $bom = $wo->bom;
        if ($bom) {
            foreach ($bom->items as $item) {
                WorkOrderMaterial::create([
                    'work_order_id' => $wo->id,
                    'product_id' => $item->product_id,
                    'sku_id' => $item->sku_id,
                    'required_qty' => $item->actual_quantity * $wo->planned_qty,
                    'issued_qty' => 0,
                    'returned_qty' => 0,
                    'warehouse_id' => $wo->warehouse_id,
                ]);
            }
        }

        $wo->load(['product', 'sku', 'bom', 'materials.product']);
        return $this->success($wo, '生产工单创建成功', 201);
    }

    public function show(WorkOrder $workOrder)
    {
        $workOrder->load(['product', 'sku', 'bom.items.product', 'warehouse',
            'operations', 'reports', 'materials.product']);
        return $this->success($workOrder);
    }

    public function update(Request $request, WorkOrder $workOrder)
    {
        if ($workOrder->status >= WorkOrder::STATUS_IN_PROGRESS) {
            return $this->error('工单已开始生产，不可修改', 400);
        }

        $validated = $request->validate([
            'priority' => 'nullable|integer|in:1,2,3',
            'planned_qty' => 'nullable|numeric|min:0.01',
            'work_hours' => 'nullable|numeric|min:0',
            'planned_start' => 'nullable|date',
            'planned_end' => 'nullable|date',
            'remark' => 'nullable|string',
        ]);

        $workOrder->update($validated);
        return $this->success($workOrder, '工单更新成功');
    }

    /**
     * 审核工单（待审 → 已审 → 生产中）
     */
    public function approve(Request $request, WorkOrder $workOrder)
    {
        if ($workOrder->status != WorkOrder::STATUS_PENDING) {
            return $this->error('只有待审核工单才能审核', 400);
        }

        $workOrder->update([
            'status' => WorkOrder::STATUS_IN_PROGRESS,
            'approver_id' => $request->user()->employee_id ?? null,
            'actual_start' => now(),
        ]);

        return $this->success($workOrder, '工单已审核，开始生产');
    }

    /**
     * 生产报工
     */
    public function report(Request $request, WorkOrder $workOrder)
    {
        $validated = $request->validate([
            'report_qty' => 'required|numeric|min:0.01',
            'qualified_qty' => 'required|numeric|min:0',
            'defective_qty' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        if ($workOrder->status != WorkOrder::STATUS_IN_PROGRESS) {
            return $this->error('只有生产中的工单才能报工', 400);
        }

        $validated['work_order_id'] = $workOrder->id;
        $validated['report_date'] = now();
        $validated['reporter_id'] = $request->user()->employee_id ?? null;

        $report = WorkOrderReport::create($validated);

        // 更新工单完工数量
        $workOrder->increment('completed_qty', $validated['qualified_qty']);
        $workOrder->increment('scrap_qty', $validated['defective_qty'] ?? 0);

        // 合格品入库
        if ($validated['qualified_qty'] > 0 && $workOrder->warehouse_id && $workOrder->sku_id) {
            $inventoryService = app(InventoryService::class);
            $inventoryService->addStock(
                $workOrder->sku_id,
                $workOrder->warehouse_id,
                $validated['qualified_qty'],
                0,
                'work_order',
                $workOrder->id,
                $request->user()->employee_id ?? 0,
                0,
                \App\Models\InventoryLog::TYPE_OTHER_IN
            );
        }

        // 检查是否完工
        if ($workOrder->fresh()->completed_qty >= $workOrder->planned_qty) {
            $workOrder->update([
                'status' => WorkOrder::STATUS_COMPLETED,
                'actual_end' => now(),
                'quality_rate' => $workOrder->completed_qty > 0
                    ? round(($workOrder->completed_qty / ($workOrder->completed_qty + $workOrder->scrap_qty)) * 100, 2)
                    : 100,
            ]);
        }

        return $this->success($report->fresh(), '报工成功');
    }

    /**
     * 结案
     */
    public function close(Request $request, WorkOrder $workOrder)
    {
        if (!in_array($workOrder->status, [WorkOrder::STATUS_COMPLETED, WorkOrder::STATUS_IN_PROGRESS])) {
            return $this->error('只有已完工或生产中的工单才能结案', 400);
        }

        $workOrder->update(['status' => WorkOrder::STATUS_CLOSED]);
        return $this->success($workOrder, '工单已结案');
    }

    /**
     * 取消
     */
    public function cancel(Request $request, WorkOrder $workOrder)
    {
        if ($workOrder->status >= WorkOrder::STATUS_COMPLETED) {
            return $this->error('已完工或已结案的工单不可取消', 400);
        }

        $workOrder->update(['status' => WorkOrder::STATUS_CANCELLED]);
        return $this->success($workOrder, '工单已取消');
    }

    /**
     * 工单统计
     */
    public function statistics()
    {
        $stats = [
            'total' => WorkOrder::count(),
            'pending' => WorkOrder::where('status', WorkOrder::STATUS_PENDING)->count(),
            'in_progress' => WorkOrder::where('status', WorkOrder::STATUS_IN_PROGRESS)->count(),
            'completed' => WorkOrder::where('status', WorkOrder::STATUS_COMPLETED)->count(),
            'closed' => WorkOrder::where('status', WorkOrder::STATUS_CLOSED)->count(),
            'cancelled' => WorkOrder::where('status', WorkOrder::STATUS_CANCELLED)->count(),
            'total_planned_qty' => WorkOrder::whereIn('status', [2, 3, 4])->sum('planned_qty'),
            'total_completed_qty' => WorkOrder::whereIn('status', [2, 3, 4])->sum('completed_qty'),
        ];
        $stats['completion_rate'] = $stats['total_planned_qty'] > 0
            ? round(($stats['total_completed_qty'] / $stats['total_planned_qty']) * 100, 2) : 0;

        return $this->success($stats);
    }
}
