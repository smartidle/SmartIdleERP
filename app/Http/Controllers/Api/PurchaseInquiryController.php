<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PurchaseInquiry;
use App\Models\PurchaseInquiryQuote;
use Illuminate\Http\Request;

class PurchaseInquiryController extends Controller
{
    public function index(Request $request)
    {
        $query = PurchaseInquiry::with(['items.product:id,name', 'quotes.supplier:id,name']);

        if ($request->has('search')) {
            $q = $request->input('search');
            $query->where('inquiry_no', 'like', "%{$q}%");
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
            'expected_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.sku_id' => 'nullable|exists:product_skus,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
        ]);

        $validated['inquiry_no'] = 'PI' . date('YmdHis') . str_pad(random_int(0, 99), 2, '0', STR_PAD_LEFT);
        $validated['status'] = PurchaseInquiry::STATUS_INQUIRING;
        $validated['employee_id'] = $request->user()->employee_id ?? null;

        $inquiry = PurchaseInquiry::create(collect($validated)->except('items')->toArray());
        foreach ($validated['items'] as $item) {
            $inquiry->items()->create($item);
        }

        $inquiry->load(['items.product']);
        return $this->success($inquiry, '询价单创建成功', 201);
    }

    public function show(PurchaseInquiry $purchaseInquiry)
    {
        $purchaseInquiry->load(['items.product', 'quotes.supplier', 'quotes.product']);
        return $this->success($purchaseInquiry);
    }

    /**
     * 添加供应商报价
     */
    public function addQuote(Request $request, PurchaseInquiry $purchaseInquiry)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'product_id' => 'required|exists:products,id',
            'sku_id' => 'nullable|exists:product_skus,id',
            'unit_price' => 'required|numeric|min:0',
            'delivery_days' => 'nullable|integer|min:0',
            'valid_until' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $validated['inquiry_id'] = $purchaseInquiry->id;

        $quote = PurchaseInquiryQuote::create($validated);

        // 更新询价单状态为报价完成
        if ($purchaseInquiry->status == PurchaseInquiry::STATUS_INQUIRING) {
            $purchaseInquiry->update(['status' => PurchaseInquiry::STATUS_QUOTED]);
        }

        return $this->success($quote, '报价添加成功', 201);
    }

    /**
     * 选中报价（用于后续转采购订单）
     */
    public function selectQuote(Request $request, PurchaseInquiryQuote $quote)
    {
        $quote->update(['is_selected' => 1]);
        return $this->success($quote, '报价已选中');
    }

    public function cancel(PurchaseInquiry $purchaseInquiry)
    {
        if ($purchaseInquiry->status == PurchaseInquiry::STATUS_CONVERTED) {
            return $this->error('已转订单的询价不可取消', 400);
        }

        $purchaseInquiry->update(['status' => PurchaseInquiry::STATUS_CANCELLED]);
        return $this->success($purchaseInquiry->fresh(), '询价单已取消');
    }
}
