<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Models\SupplierProduct;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    /**
     * 获取供应商列表
     */
    public function index(Request $request)
    {
        $query = Supplier::with(['products']);

        // 搜索
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // 状态过滤
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        $suppliers = $query->orderBy('id', 'desc')
            ->paginate($request->input('per_page', 20));

        return $this->paginate($suppliers, '获取成功');
    }

    /**
     * 创建供应商
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|unique:suppliers,code',
            'contact_person' => 'nullable|string|max:128',
            'phone' => 'nullable|string|max:32',
            'email' => 'nullable|email|max:128',
        ]);

        $data = $request->only([
            'name', 'code', 'contact_person', 'phone', 'mobile', 'email',
            'address', 'country', 'city', 'bank_name', 'bank_account',
            'payment_terms', 'lead_time', 'rating', 'status', 'remark',
        ]);

        if (empty($data['code'])) {
            $data['code'] = 'S' . date('Ymd') . str_pad(Supplier::count() + 1, 4, '0', STR_PAD_LEFT);
        }

        $supplier = Supplier::create($data);

        return $this->success($supplier, '创建成功', 201);
    }

    /**
     * 显示供应商
     */
    public function show($id)
    {
        $supplier = Supplier::with(['products.product', 'products.sku'])
            ->findOrFail($id);

        $supplier->product_count = $supplier->products()->count();
        $supplier->order_count = $supplier->purchaseOrders()->count();

        return $this->success($supplier);
    }

    /**
     * 更新供应商
     */
    public function update(Request $request, $id)
    {
        $supplier = Supplier::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
        ]);

        $supplier->update($request->only([
            'name', 'contact_person', 'phone', 'mobile', 'email',
            'address', 'country', 'city', 'bank_name', 'bank_account',
            'payment_terms', 'lead_time', 'rating', 'status', 'remark',
        ]));

        return $this->success($supplier, '更新成功');
    }

    /**
     * 删除供应商
     */
    public function destroy($id)
    {
        $supplier = Supplier::findOrFail($id);

        if ($supplier->purchaseOrders()->exists()) {
            return $this->error('该供应商已有订单关联，无法删除');
        }

        $supplier->products()->delete();
        $supplier->delete();

        return $this->success(null, '删除成功');
    }

    /**
     * 搜索供应商
     */
    public function search(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:1',
        ]);

        $search = $request->input('q');

        $suppliers = Supplier::where('status', 1)
            ->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            })
            ->limit(20)
            ->get();

        return $this->success($suppliers);
    }
}
