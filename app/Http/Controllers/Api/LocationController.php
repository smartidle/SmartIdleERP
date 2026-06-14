<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\Warehouse;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function index(Request $request)
    {
        $query = Location::with(['warehouse:id,code,name']);

        if ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->input('warehouse_id'));
        }
        if ($request->has('search')) {
            $q = $request->input('search');
            $query->where(function ($sq) use ($q) {
                $sq->where('code', 'like', "%{$q}%")
                   ->orWhere('zone', 'like', "%{$q}%");
            });
        }
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        $list = $query->orderBy('id', 'desc')->paginate($request->input('per_page', 50));
        return $this->success($list);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'code' => 'required|string|max:32',
            'zone' => 'nullable|string|max:32',
            'shelf' => 'nullable|string|max:32',
            'layer' => 'nullable|integer|min:0',
            'position' => 'nullable|string|max:32',
            'capacity' => 'nullable|numeric|min:0',
            'status' => 'nullable|integer|in:0,1',
        ]);

        $location = Location::create($validated);
        return $this->success($location, '库位创建成功', 201);
    }

    public function show(Location $location)
    {
        $location->load(['warehouse:id,code,name', 'inventories']);
        return $this->success($location);
    }

    public function update(Request $request, Location $location)
    {
        $validated = $request->validate([
            'warehouse_id' => 'sometimes|exists:warehouses,id',
            'code' => 'sometimes|string|max:32',
            'zone' => 'nullable|string|max:32',
            'shelf' => 'nullable|string|max:32',
            'layer' => 'nullable|integer|min:0',
            'position' => 'nullable|string|max:32',
            'capacity' => 'nullable|numeric|min:0',
            'status' => 'nullable|integer|in:0,1',
        ]);

        $location->update($validated);
        return $this->success($location, '库位更新成功');
    }

    public function destroy(Location $location)
    {
        if ($location->inventories()->count() > 0) {
            return $this->error('该库位有库存数据，无法删除', 400);
        }
        $location->delete();
        return $this->success(null, '库位删除成功');
    }

    /**
     * 指定仓库的库位列表
     */
    public function byWarehouse($warehouseId)
    {
        $locations = Location::where('warehouse_id', $warehouseId)
            ->where('status', 1)
            ->get(['id', 'code', 'zone', 'shelf', 'layer']);
        return $this->success($locations);
    }
}
