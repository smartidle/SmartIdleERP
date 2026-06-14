<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerAddress;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    /**
     * 获取客户列表
     */
    public function index(Request $request)
    {
        $query = Customer::with(['addresses']);

        // 搜索
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // 等级过滤
        if ($request->has('level')) {
            $query->where('level', $request->input('level'));
        }

        // 状态过滤
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        $customers = $query->orderBy('id', 'desc')
            ->paginate($request->input('per_page', 20));

        return $this->paginate($customers, '获取成功');
    }

    /**
     * 创建客户
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|unique:customers,code',
            'level' => 'nullable|integer|in:1,2,3,4',
            'contact_person' => 'nullable|string|max:128',
            'phone' => 'nullable|string|max:32',
            'mobile' => 'nullable|string|max:32',
            'email' => 'nullable|email|max:128',
            'address' => 'nullable|string',
            'status' => 'nullable|integer|in:0,1',
        ]);

        $data = $request->only([
            'name', 'code', 'level', 'contact_person', 'phone', 'mobile',
            'email', 'address', 'country', 'city', 'tax_number', 'discount_rate',
            'credit_limit', 'payment_terms', 'status', 'remark',
        ]);

        if (empty($data['code'])) {
            $data['code'] = 'C' . date('Ymd') . str_pad(Customer::count() + 1, 4, '0', STR_PAD_LEFT);
        }

        $customer = Customer::create($data);

        return $this->success($customer, '创建成功', 201);
    }

    /**
     * 显示客户
     */
    public function show($id)
    {
        $customer = Customer::with(['addresses', 'prices.product', 'prices.sku'])
            ->findOrFail($id);

        // 计算统计数据
        $customer->order_count = $customer->salesOrders()->count();
        $customer->total_amount = $customer->salesOrders()->sum('total_amount');
        $customer->paid_amount = $customer->salesOrders()->sum('paid_amount');

        return $this->success($customer);
    }

    /**
     * 更新客户
     */
    public function update(Request $request, $id)
    {
        $customer = Customer::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
        ]);

        $customer->update($request->only([
            'name', 'level', 'contact_person', 'phone', 'mobile',
            'email', 'address', 'country', 'city', 'tax_number', 'discount_rate',
            'credit_limit', 'payment_terms', 'status', 'remark',
        ]));

        return $this->success($customer, '更新成功');
    }

    /**
     * 删除客户
     */
    public function destroy($id)
    {
        $customer = Customer::findOrFail($id);

        if ($customer->salesOrders()->exists()) {
            return $this->error('该客户已有订单关联，无法删除');
        }

        $customer->addresses()->delete();
        $customer->prices()->delete();
        $customer->delete();

        return $this->success(null, '删除成功');
    }

    /**
     * 获取客户收货地址列表
     */
    public function addresses($id)
    {
        $customer = Customer::findOrFail($id);
        $addresses = $customer->addresses()->orderBy('is_default', 'desc')->get();
        return $this->success($addresses);
    }

    /**
     * 添加客户收货地址
     */
    public function addAddress(Request $request, $id)
    {
        $customer = Customer::findOrFail($id);

        $request->validate([
            'address' => 'required|string',
            'contact_person' => 'nullable|string|max:128',
            'phone' => 'nullable|string|max:32',
            'is_default' => 'nullable|integer|in:0,1',
        ]);

        $data = $request->only(['address', 'contact_person', 'phone', 'is_default']);

        // 如果设为默认，取消其他默认
        if (($data['is_default'] ?? 0) == 1) {
            $customer->addresses()->update(['is_default' => 0]);
        }

        $address = $customer->addresses()->create($data);

        return $this->success($address, '添加成功', 201);
    }

    /**
     * 搜索客户
     */
    public function search(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:1',
        ]);

        $search = $request->input('q');

        $customers = Customer::where('status', 1)
            ->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%");
            })
            ->limit(20)
            ->get();

        return $this->success($customers);
    }
}
