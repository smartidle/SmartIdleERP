<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bom;
use App\Models\BomItem;
use Illuminate\Http\Request;

class BomController extends Controller
{
    public function index(Request $request)
    {
        $query = Bom::with(['product:id,name,spec', 'sku:id,sku_code', 'items']);

        if ($request->has('search')) {
            $q = $request->input('search');
            $query->where(function ($sq) use ($q) {
                $sq->where('code', 'like', "%{$q}%")
                   ->orWhereHas('product', fn($p) => $p->where('name', 'like', "%{$q}%"));
            });
        }
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->has('product_id')) {
            $query->where('product_id', $request->input('product_id'));
        }

        $list = $query->orderBy('id', 'desc')->paginate($request->input('per_page', 20));
        return $this->success($list);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:32|unique:boms,code',
            'product_id' => 'required|exists:products,id',
            'sku_id' => 'nullable|exists:product_skus,id',
            'version' => 'nullable|string|max:16',
            'quantity' => 'nullable|numeric|min:0',
            'unit_cost' => 'nullable|numeric|min:0',
            'status' => 'nullable|integer|in:0,1,2',
            'effective_date' => 'nullable|date',
            'invalid_date' => 'nullable|date',
            'remark' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.sku_id' => 'nullable|exists:product_skus,id',
            'items.*.quantity' => 'required|numeric|min:0',
            'items.*.unit' => 'nullable|string|max:32',
            'items.*.loss_rate' => 'nullable|numeric|min:0',
            'items.*.remark' => 'nullable|string',
        ]);

        $bom = Bom::create(collect($validated)->except('items')->toArray());

        foreach ($validated['items'] as $item) {
            $itemData = $item;
            $itemData['bom_id'] = $bom->id;
            // 自动计算实际用量（含损耗）
            $lossRate = $item['loss_rate'] ?? 0;
            $itemData['actual_quantity'] = $item['quantity'] * (1 + $lossRate / 100);
            BomItem::create($itemData);
        }

        $bom->load(['product', 'sku', 'items.product']);
        return $this->success($bom, 'BOM创建成功', 201);
    }

    public function show(Bom $bom)
    {
        $bom->load(['product', 'sku', 'items.product', 'items.sku', 'workOrders' => fn($q) => $q->latest()->limit(5)]);
        return $this->success($bom);
    }

    public function update(Request $request, Bom $bom)
    {
        $validated = $request->validate([
            'version' => 'nullable|string|max:16',
            'quantity' => 'nullable|numeric|min:0',
            'unit_cost' => 'nullable|numeric|min:0',
            'status' => 'nullable|integer|in:0,1,2',
            'effective_date' => 'nullable|date',
            'invalid_date' => 'nullable|date',
            'remark' => 'nullable|string',
        ]);

        $bom->update($validated);
        return $this->success($bom, 'BOM更新成功');
    }

    public function destroy(Bom $bom)
    {
        if ($bom->workOrders()->count() > 0) {
            return $this->error('该BOM有生产工单关联，无法删除', 400);
        }
        $bom->items()->delete();
        $bom->delete();
        return $this->success(null, 'BOM删除成功');
    }

    /**
     * 更新BOM明细（整体替换）
     */
    public function updateItems(Request $request, Bom $bom)
    {
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'nullable|exists:bom_items,id',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.sku_id' => 'nullable|exists:product_skus,id',
            'items.*.quantity' => 'required|numeric|min:0',
            'items.*.unit' => 'nullable|string|max:32',
            'items.*.loss_rate' => 'nullable|numeric|min:0',
            'items.*.remark' => 'nullable|string',
        ]);

        $existingIds = collect($validated['items'])->pluck('id')->filter()->toArray();
        $bom->items()->whereNotIn('id', $existingIds)->delete();

        foreach ($validated['items'] as $item) {
            $itemData = $item;
            $lossRate = $item['loss_rate'] ?? 0;
            $itemData['actual_quantity'] = $item['quantity'] * (1 + $lossRate / 100);

            if (isset($item['id'])) {
                BomItem::where('id', $item['id'])->update($itemData);
            } else {
                $itemData['bom_id'] = $bom->id;
                BomItem::create($itemData);
            }
        }

        $bom->load(['items.product']);
        return $this->success($bom, 'BOM明细更新成功');
    }
}
