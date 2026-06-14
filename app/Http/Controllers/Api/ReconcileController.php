<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Reconcile;
use App\Models\FinanceReceipt;
use Illuminate\Http\Request;

class ReconcileController extends Controller
{
    public function index(Request $request)
    {
        $query = Reconcile::with(['order:id,order_no,total_amount']);

        if ($request->has('order_id')) {
            $query->where('order_id', $request->input('order_id'));
        }

        $list = $query->orderBy('id', 'desc')->paginate($request->input('per_page', 20));
        return $this->success($list);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'receipt_id' => 'nullable|integer',
            'order_id' => 'required|exists:sales_orders,id',
            'amount' => 'required|numeric|min:0.01',
        ]);

        $reconcile = Reconcile::create($validated);
        $reconcile->load(['order:id,order_no']);
        return $this->success($reconcile, '核销记录创建成功', 201);
    }
}
