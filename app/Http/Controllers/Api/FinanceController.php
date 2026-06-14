<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FinanceAccount;
use App\Models\FinanceReceipt;
use App\Models\FinancePayment;
use App\Models\SalesOrder;
use App\Models\PurchaseOrder;
use Illuminate\Http\Request;

class FinanceController extends Controller
{
    /**
     * 收款单列表
     */
    public function receipts(Request $request)
    {
        $query = FinanceReceipt::with('customer');

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->input('customer_id'));
        }

        if ($request->has('date_from')) {
            $query->where('receipt_date', '>=', $request->input('date_from'));
        }

        if ($request->has('date_to')) {
            $query->where('receipt_date', '<=', $request->input('date_to'));
        }

        $receipts = $query->orderBy('id', 'desc')
            ->paginate($request->input('per_page', 20));

        return $this->success($receipts);
    }

    /**
     * 创建收款单
     */
    public function createReceipt(Request $request)
    {
        $request->validate([
            'receipt_no' => 'required|string|max:32',
            'customer_id' => 'required|exists:customers,id',
            'amount' => 'required|numeric|min:0.01',
            'receipt_date' => 'required|date',
            'payment_method' => 'required|in:1,2,3,4',
        ]);

        $receipt = FinanceReceipt::create([
            'receipt_no' => $request->input('receipt_no'),
            'customer_id' => $request->input('customer_id'),
            'amount' => $request->input('amount'),
            'actual_amount' => $request->input('amount'),
            'receipt_date' => $request->input('receipt_date'),
            'payment_method' => $request->input('payment_method'),
            'account_id' => $request->input('account_id'),
            'status' => 2, // 已确认
            'remark' => $request->input('remark'),
            'employee_id' => $request->user()->id,
        ]);

        // 关联销售订单
        if ($request->has('order_id')) {
            $receipt->order_type = 'sales';
            $receipt->order_id = $request->input('order_id');
            $receipt->save();

            // 更新销售订单已付款金额
            $order = SalesOrder::find($request->input('order_id'));
            if ($order) {
                $order->paid_amount += $request->input('amount');
                $order->save();
            }
        }

        return $this->success($receipt, 'Receipt created', 201);
    }

    /**
     * 付款单列表
     */
    public function payments(Request $request)
    {
        $query = FinancePayment::with('supplier');

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('supplier_id')) {
            $query->where('supplier_id', $request->input('supplier_id'));
        }

        if ($request->has('date_from')) {
            $query->where('payment_date', '>=', $request->input('date_from'));
        }

        if ($request->has('date_to')) {
            $query->where('payment_date', '<=', $request->input('date_to'));
        }

        $payments = $query->orderBy('id', 'desc')
            ->paginate($request->input('per_page', 20));

        return $this->success($payments);
    }

    /**
     * 创建付款单
     */
    public function createPayment(Request $request)
    {
        $request->validate([
            'payment_no' => 'required|string|max:32',
            'supplier_id' => 'required|exists:suppliers,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'payment_method' => 'required|in:1,2,3,4',
        ]);

        $payment = FinancePayment::create([
            'payment_no' => $request->input('payment_no'),
            'supplier_id' => $request->input('supplier_id'),
            'amount' => $request->input('amount'),
            'actual_amount' => $request->input('amount'),
            'payment_date' => $request->input('payment_date'),
            'payment_method' => $request->input('payment_method'),
            'account_id' => $request->input('account_id'),
            'status' => 2, // 已确认
            'remark' => $request->input('remark'),
            'employee_id' => $request->user()->id,
        ]);

        // 关联采购订单
        if ($request->has('order_id')) {
            $payment->order_type = 'purchase';
            $payment->order_id = $request->input('order_id');
            $payment->save();

            // 更新采购订单已付款金额
            $order = PurchaseOrder::find($request->input('order_id'));
            if ($order) {
                $order->paid_amount += $request->input('amount');
                $order->save();
            }
        }

        return $this->success($payment, 'Payment created', 201);
    }

    /**
     * 账户列表
     */
    public function accounts()
    {
        $accounts = FinanceAccount::where('status', 1)->get();
        return $this->success($accounts);
    }

    /**
     * 财务统计
     */
    public function statistics(Request $request)
    {
        $dateFrom = $request->input('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->input('date_to', now()->format('Y-m-d'));

        // 本期收入
        $totalReceipt = FinanceReceipt::where('status', 2)
            ->whereBetween('payment_date', [$dateFrom, $dateTo])
            ->sum('amount');

        // 本期支出
        $totalPayment = FinancePayment::where('status', 2)
            ->whereBetween('payment_date', [$dateFrom, $dateTo])
            ->sum('amount');

        // 应收款
        $receivable = SalesOrder::whereIn('status', [2, 3, 4])
            ->selectRaw('SUM(total_amount - paid_amount) as total')
            ->value('total') ?? 0;

        // 应付款
        $payable = PurchaseOrder::whereIn('status', [2, 3])
            ->selectRaw('SUM(total_amount - paid_amount) as total')
            ->value('total') ?? 0;

        return $this->success([
            'total_receipt' => $totalReceipt,
            'total_payment' => $totalPayment,
            'net_profit' => $totalReceipt - $totalPayment,
            'receivable' => $receivable,
            'payable' => $payable,
        ]);
    }
}
