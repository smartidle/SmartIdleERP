<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FinanceInvoice;
use App\Models\InvoiceMatch;
use App\Models\SalesOrder;
use App\Models\PurchaseOrder;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    /**
     * 发票列表
     */
    public function index(Request $request)
    {
        $query = FinanceInvoice::with(['customer', 'supplier', 'matches']);

        if ($request->has('type')) {
            $query->where('type', $request->input('type'));
        }
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->input('customer_id'));
        }
        if ($request->has('supplier_id')) {
            $query->where('supplier_id', $request->input('supplier_id'));
        }
        if ($request->has('date_from')) {
            $query->where('invoice_date', '>=', $request->input('date_from'));
        }
        if ($request->has('date_to')) {
            $query->where('invoice_date', '<=', $request->input('date_to'));
        }
        if ($request->has('search')) {
            $query->where('invoice_no', 'like', "%{$request->input('search')}%");
        }

        $invoices = $query->orderBy('id', 'desc')->paginate($request->input('per_page', 20));
        return $this->success($invoices);
    }

    /**
     * 创建发票草稿
     */
    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|in:1,2',
            'invoice_no' => 'required|string|max:64|unique:finance_invoices,invoice_no',
            'customer_id' => 'nullable|exists:customers,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'amount' => 'required|numeric|min:0.01',
            'tax_amount' => 'nullable|numeric|min:0',
            'invoice_date' => 'required|date',
            'order_id' => 'nullable|integer',
            'notes' => 'nullable|string',
        ]);

        $invoice = FinanceInvoice::create([
            'invoice_no' => $request->input('invoice_no'),
            'type' => $request->input('type'),
            'customer_id' => $request->input('customer_id'),
            'supplier_id' => $request->input('supplier_id'),
            'amount' => $request->input('amount'),
            'tax_amount' => $request->input('tax_amount', 0),
            'invoice_date' => $request->input('invoice_date'),
            'status' => FinanceInvoice::STATUS_DRAFT,
            'notes' => $request->input('notes'),
        ]);

        // 如果关联了订单，同时创建匹配记录
        if ($request->has('order_id') && $request->input('order_id')) {
            InvoiceMatch::create([
                'invoice_id' => $invoice->id,
                'order_id' => $request->input('order_id'),
                'amount' => $request->input('amount'),
            ]);
        }

        $invoice->load('customer', 'supplier', 'matches');
        return $this->success($invoice, 'Invoice created', 201);
    }

    /**
     * 查看发票详情
     */
    public function show(FinanceInvoice $invoice)
    {
        $invoice->load(['customer', 'supplier', 'matches.order', 'matches.receipt', 'matches.payment']);
        return $this->success($invoice);
    }

    /**
     * 开具发票（状态从草稿改为已开）
     */
    public function issue(Request $request, FinanceInvoice $invoice)
    {
        if ($invoice->status === FinanceInvoice::STATUS_VOID) {
            return $this->error('Voided invoice cannot be issued', 400);
        }
        if ($invoice->status === FinanceInvoice::STATUS_ISSUED) {
            return $this->error('Invoice already issued', 400);
        }

        $invoice->update(['status' => FinanceInvoice::STATUS_ISSUED]);

        // 销售发票：更新关联的销售订单收款状态
        if ($invoice->type === FinanceInvoice::TYPE_SALES && $invoice->customer_id) {
            // 查找订单匹配记录，更新收款单核销状态
            foreach ($invoice->matches as $match) {
                if ($match->receipt_id) {
                    $match->receipt->update(['status' => 2]); // 已核销
                }
            }
        }

        // 采购发票：更新关联的采购订单付款状态
        if ($invoice->type === FinanceInvoice::TYPE_PURCHASE && $invoice->supplier_id) {
            foreach ($invoice->matches as $match) {
                if ($match->payment_id) {
                    $match->payment->update(['status' => 2]); // 已核销
                }
            }
        }

        return $this->success($invoice, 'Invoice issued');
    }

    /**
     * 作废发票
     */
    public function void(Request $request, FinanceInvoice $invoice)
    {
        $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        if ($invoice->status === FinanceInvoice::STATUS_VOID) {
            return $this->error('Invoice already voided', 400);
        }

        $invoice->void($request->input('reason'));

        // 恢复关联的收款/付款单状态
        foreach ($invoice->matches as $match) {
            if ($match->receipt_id && $match->receipt) {
                $match->receipt->update(['status' => 1]); // 恢复为待核销
            }
            if ($match->payment_id && $match->payment) {
                $match->payment->update(['status' => 1]);
            }
        }

        return $this->success(null, 'Invoice voided');
    }

    /**
     * 为发票添加匹配（关联收款/付款单）
     */
    public function addMatch(Request $request, FinanceInvoice $invoice)
    {
        $request->validate([
            'receipt_id' => 'nullable|exists:finance_receipts,id',
            'payment_id' => 'nullable|exists:finance_payments,id',
            'order_id' => 'nullable|integer',
            'amount' => 'required|numeric|min:0.01',
        ]);

        if ($invoice->status === FinanceInvoice::STATUS_VOID) {
            return $this->error('Cannot match voided invoice', 400);
        }

        // 检查金额不能超过发票金额
        $totalMatched = $invoice->matches()->sum('amount');
        $newTotal = $totalMatched + $request->input('amount');
        if ($newTotal > $invoice->amount) {
            return $this->error('Match amount exceeds invoice total amount', 400);
        }

        InvoiceMatch::create([
            'invoice_id' => $invoice->id,
            'receipt_id' => $request->input('receipt_id'),
            'payment_id' => $request->input('payment_id'),
            'order_id' => $request->input('order_id'),
            'amount' => $request->input('amount'),
        ]);

        $invoice->load('matches');
        return $this->success($invoice, 'Match added');
    }

    /**
     * 发票统计
     */
    public function statistics(Request $request)
    {
        $type = $request->input('type', 1); // 1=销售发票 2=采购发票

        $issued = FinanceInvoice::where('type', $type)
            ->where('status', FinanceInvoice::STATUS_ISSUED)
            ->selectRaw('COUNT(*) as count, SUM(amount) as total_amount, SUM(tax_amount) as total_tax')
            ->first();

        return $this->success([
            'type' => $type,
            'issued_count' => $issued->count ?? 0,
            'issued_amount' => $issued->total_amount ?? 0,
            'issued_tax' => $issued->total_tax ?? 0,
            'void_count' => FinanceInvoice::where('type', $type)->where('status', FinanceInvoice::STATUS_VOID)->count(),
            'draft_count' => FinanceInvoice::where('type', $type)->where('status', FinanceInvoice::STATUS_DRAFT)->count(),
        ]);
    }
}