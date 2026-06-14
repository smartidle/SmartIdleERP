<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SalesQuotation;
use App\Models\SalesQuotationItem;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Services\SalesOrderService;
use Illuminate\Http\Request;

class QuotationController extends Controller
{
    /**
     * 报价单列表
     */
    public function index(Request $request)
    {
        $query = SalesQuotation::with(['customer', 'employee']);

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->input('customer_id'));
        }
        if ($request->has('search')) {
            $query->where('quotation_no', 'like', "%{$request->input('search')}%");
        }
        if ($request->has('date_from')) {
            $query->where('quotation_date', '>=', $request->input('date_from'));
        }
        if ($request->has('date_to')) {
            $query->where('quotation_date', '<=', $request->input('date_to'));
        }

        $quotations = $query->orderBy('id', 'desc')->paginate($request->input('per_page', 20));
        return $this->success($quotations);
    }

    /**
     * 创建报价单
     */
    public function store(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.001',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        $quotationNo = 'QT' . date('Ymd') . str_pad(SalesQuotation::count() + 1, 6, '0', STR_PAD_LEFT);

        $quotation = SalesQuotation::create([
            'quotation_no' => $quotationNo,
            'customer_id' => $request->input('customer_id'),
            'quotation_date' => $request->input('quotation_date', date('Y-m-d')),
            'valid_until' => $request->input('valid_until'),
            'discount_rate' => $request->input('discount_rate', 0),
            'tax_rate' => $request->input('tax_rate', 0),
            'status' => SalesQuotation::STATUS_DRAFT,
            'notes' => $request->input('notes'),
            'terms' => $request->input('terms'),
            'employee_id' => $request->user()->id,
        ]);

        $totalAmount = 0;
        $salesOrderService = app(SalesOrderService::class);
        foreach ($request->input('items') as $item) {
            $unitPrice = $item['unit_price'];
            $qty = $item['quantity'];
            $lineDiscount = $item['discount_rate'] ?? 0;
            $lineDiscountAmt = $qty * $unitPrice * $lineDiscount / 100;
            $subtotal = $qty * $unitPrice - $lineDiscountAmt;

            SalesQuotationItem::create([
                'quotation_id' => $quotation->id,
                'product_id' => $item['product_id'],
                'sku_id' => $item['sku_id'] ?? null,
                'product_name' => $item['product_name'] ?? '',
                'sku_code' => $item['sku_code'] ?? null,
                'specs' => $item['specs'] ?? null,
                'quantity' => $qty,
                'unit' => $item['unit'] ?? '个',
                'unit_price' => $unitPrice,
                'cost_price' => $item['cost_price'] ?? 0,
                'subtotal' => $subtotal,
                'discount_rate' => $lineDiscount,
                'discount_amount' => $lineDiscountAmt,
                'remark' => $item['remark'] ?? null,
            ]);

            $totalAmount += $subtotal;
        }

        $discountAmount = $totalAmount * $quotation->discount_rate / 100;
        $taxableAmount = $totalAmount - $discountAmount;
        $taxAmount = $taxableAmount * $quotation->tax_rate / 100;
        $finalAmount = $taxableAmount + $taxAmount;

        $quotation->update([
            'total_amount' => $totalAmount,
            'discount_amount' => $discountAmount,
            'tax_amount' => $taxAmount,
            'final_amount' => $finalAmount,
        ]);

        $quotation->load('items');
        return $this->success($quotation, 'Quotation created', 201);
    }

    /**
     * 查看报价单详情
     */
    public function show(SalesQuotation $quotation)
    {
        $quotation->load(['customer', 'employee', 'items.product', 'items.sku']);
        return $this->success($quotation);
    }

    /**
     * 更新报价单（仅草稿状态可修改）
     */
    public function update(Request $request, SalesQuotation $quotation)
    {
        if ($quotation->status !== SalesQuotation::STATUS_DRAFT) {
            return $this->error('Only draft quotations can be updated', 400);
        }

        if ($request->has('items')) {
            $quotation->items()->delete();
            $totalAmount = 0;
            foreach ($request->input('items') as $item) {
                $unitPrice = $item['unit_price'] ?? 0;
                $qty = $item['quantity'] ?? 0;
                $lineDiscount = $item['discount_rate'] ?? 0;
                $lineDiscountAmt = $qty * $unitPrice * $lineDiscount / 100;
                $subtotal = $qty * $unitPrice - $lineDiscountAmt;

                SalesQuotationItem::create([
                    'quotation_id' => $quotation->id,
                    'product_id' => $item['product_id'],
                    'sku_id' => $item['sku_id'] ?? null,
                    'product_name' => $item['product_name'] ?? '',
                    'sku_code' => $item['sku_code'] ?? null,
                    'specs' => $item['specs'] ?? null,
                    'quantity' => $qty,
                    'unit' => $item['unit'] ?? '个',
                    'unit_price' => $unitPrice,
                    'cost_price' => $item['cost_price'] ?? 0,
                    'subtotal' => $subtotal,
                    'discount_rate' => $lineDiscount,
                    'discount_amount' => $lineDiscountAmt,
                    'remark' => $item['remark'] ?? null,
                ]);
                $totalAmount += $subtotal;
            }

            $discountAmount = $totalAmount * ($request->input('discount_rate', $quotation->discount_rate)) / 100;
            $taxableAmount = $totalAmount - $discountAmount;
            $taxRate = $request->input('tax_rate', $quotation->tax_rate);
            $taxAmount = $taxableAmount * $taxRate / 100;
            $finalAmount = $taxableAmount + $taxAmount;

            $quotation->update([
                'total_amount' => $totalAmount,
                'discount_rate' => $request->input('discount_rate', $quotation->discount_rate),
                'discount_amount' => $discountAmount,
                'tax_rate' => $taxRate,
                'tax_amount' => $taxAmount,
                'final_amount' => $finalAmount,
                'valid_until' => $request->input('valid_until', $quotation->valid_until),
                'notes' => $request->input('notes', $quotation->notes),
                'terms' => $request->input('terms', $quotation->terms),
            ]);
        }

        $quotation->load('items');
        return $this->success($quotation, 'Quotation updated');
    }

    /**
     * 发送报价单
     */
    public function send(SalesQuotation $quotation)
    {
        if ($quotation->status !== SalesQuotation::STATUS_DRAFT) {
            return $this->error('Only draft quotations can be sent', 400);
        }
        $quotation->update([
            'status' => SalesQuotation::STATUS_SENT,
            'sent_at' => now(),
        ]);
        return $this->success(null, 'Quotation sent');
    }

    /**
     * 客户接受报价
     */
    public function accept(SalesQuotation $quotation)
    {
        if ($quotation->status !== SalesQuotation::STATUS_SENT) {
            return $this->error('Only sent quotations can be accepted', 400);
        }
        $quotation->update([
            'status' => SalesQuotation::STATUS_ACCEPTED,
            'accepted_at' => now(),
        ]);
        return $this->success(null, 'Quotation accepted');
    }

    /**
     * 客户拒绝报价
     */
    public function reject(Request $request, SalesQuotation $quotation)
    {
        if ($quotation->status !== SalesQuotation::STATUS_SENT) {
            return $this->error('Only sent quotations can be rejected', 400);
        }
        $quotation->update([
            'status' => SalesQuotation::STATUS_REJECTED,
            'notes' => ($quotation->notes ? $quotation->notes . "\n" : '') . '拒绝原因: ' . ($request->input('reason') ?? ''),
        ]);
        return $this->success(null, 'Quotation rejected');
    }

    /**
     * 将报价单转为销售订单
     */
    public function convertToOrder(Request $request, SalesQuotation $quotation)
    {
        if (!in_array($quotation->status, [SalesQuotation::STATUS_ACCEPTED, SalesQuotation::STATUS_SENT])) {
            return $this->error('Quotation must be accepted or sent before conversion', 400);
        }

        try {
            $salesOrderService = app(SalesOrderService::class);
            $orderData = [
                'customer_id' => $quotation->customer_id,
                'quotation_id' => $quotation->id,
                'notes' => $quotation->notes,
                'items' => $quotation->items->map(fn($item) => [
                    'product_id' => $item->product_id,
                    'sku_id' => $item->sku_id,
                    'product_name' => $item->product_name,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'cost_price' => $item->cost_price,
                    'remark' => $item->remark,
                ])->toArray(),
            ];

            $order = $salesOrderService->createOrder($orderData, $request->user());

            $quotation->update([
                'status' => SalesQuotation::STATUS_CONVERTED,
                'converted_order_id' => $order->id,
            ]);

            return $this->success([
                'quotation' => $quotation,
                'order' => $order,
            ], 'Converted to sales order', 201);
        } catch (\Exception $e) {
            return $this->error('Conversion failed: ' . $e->getMessage());
        }
    }

    /**
     * 报价单统计
     */
    public function statistics()
    {
        $total = SalesQuotation::count();
        $sent = SalesQuotation::where('status', SalesQuotation::STATUS_SENT)->count();
        $accepted = SalesQuotation::where('status', SalesQuotation::STATUS_ACCEPTED)->count();
        $converted = SalesQuotation::where('status', SalesQuotation::STATUS_CONVERTED)->count();
        $totalAmount = SalesQuotation::whereIn('status', [SalesQuotation::STATUS_ACCEPTED, SalesQuotation::STATUS_CONVERTED])->sum('final_amount');

        return $this->success([
            'total' => $total,
            'sent' => $sent,
            'accepted' => $accepted,
            'converted' => $converted,
            'conversion_rate' => $sent > 0 ? round($converted / $sent * 100, 2) : 0,
            'total_amount' => $totalAmount,
        ]);
    }
}