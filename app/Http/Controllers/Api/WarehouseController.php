<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    public function index(Request $request)
    {
        $query = Warehouse::with(['manager:id,name']);

        if ($request->has('search')) {
            $q = $request->input('search');
            $query->where(function ($sq) use ($q) {
                $sq->where('name', 'like', "%{$q}%")
                   ->orWhere('code', 'like', "%{$q}%");
            });
        }
        if ($request->has('type')) {
            $query->where('type', $request->input('type'));
        }
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        $list = $query->orderBy('is_default', 'desc')->orderBy('id', 'desc')
            ->paginate($request->input('per_page', 20));
        return $this->success($list);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:32|unique:warehouses,code',
            'name' => 'required|string|max:128',
            'type' => 'nullable|integer|in:1,2,3,4,5',
            'address' => 'nullable|string|max:500',
            'manager_id' => 'nullable|exists:employees,id',
            'is_default' => 'nullable|integer|in:0,1',
            'capacity' => 'nullable|numeric|min:0',
            'status' => 'nullable|integer|in:0,1',
        ]);

        $warehouse = Warehouse::create($validated);
        return $this->success($warehouse, '仓库创建成功', 201);
    }

    public function show(Warehouse $warehouse)
    {
        $warehouse->load(['manager:id,name', 'locations']);
        return $this->success($warehouse);
    }

    public function update(Request $request, Warehouse $warehouse)
    {
        $validated = $request->validate([
            'code' => 'sometimes|string|max:32|unique:warehouses,code,' . $warehouse->id,
            'name' => 'sometimes|string|max:128',
            'type' => 'nullable|integer|in:1,2,3,4,5',
            'address' => 'nullable|string|max:500',
            'manager_id' => 'nullable|exists:employees,id',
            'is_default' => 'nullable|integer|in:0,1',
            'capacity' => 'nullable|numeric|min:0',
            'status' => 'nullable|integer|in:0,1',
        ]);

        // 如果设为默认，取消其他默认
        if (($validated['is_default'] ?? 0) == 1) {
            Warehouse::where('is_default', 1)->update(['is_default' => 0]);
        }

        $warehouse->update($validated);
        return $this->success($warehouse, '仓库更新成功');
    }

    public function destroy(Warehouse $warehouse)
    {
        if ($warehouse->inventories()->count() > 0) {
            return $this->error('该仓库有库存数据，无法删除', 400);
        }
        $warehouse->delete();
        return $this->success(null, '仓库删除成功');
    }

    /**
     * 获取所有仓库（下拉选择用）
     */
    public function all()
    {
        $warehouses = Warehouse::where('status', 1)->orderBy('is_default', 'desc')->get(['id', 'code', 'name', 'type']);
        return $this->success($warehouses);
    }
}
