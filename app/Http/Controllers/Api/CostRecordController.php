<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CostRecord;
use Illuminate\Http\Request;

class CostRecordController extends Controller
{
    public function index(Request $request)
    {
        $query = CostRecord::with(['product:id,name,spec', 'sku:id,sku_code']);

        if ($request->has('product_id')) {
            $query->where('product_id', $request->input('product_id'));
        }
        if ($request->has('cost_type')) {
            $query->where('cost_type', $request->input('cost_type'));
        }
        if ($request->has('start_date')) {
            $query->where('create_time', '>=', $request->input('start_date'));
        }
        if ($request->has('end_date')) {
            $query->where('create_time', '<=', $request->input('end_date') . ' 23:59:59');
        }

        $list = $query->orderBy('id', 'desc')->paginate($request->input('per_page', 20));
        return $this->success($list);
    }

    public function statistics(Request $request)
    {
        $query = CostRecord::query();
        if ($request->has('start_date')) {
            $query->where('create_time', '>=', $request->input('start_date'));
        }
        if ($request->has('end_date')) {
            $query->where('create_time', '<=', $request->input('end_date') . ' 23:59:59');
        }

        $stats = [
            'total_cost' => $query->sum('amount'),
            'purchase_cost' => (clone $query)->where('cost_type', CostRecord::TYPE_PURCHASE)->sum('amount'),
            'production_cost' => (clone $query)->where('cost_type', CostRecord::TYPE_PRODUCTION)->sum('amount'),
            'other_cost' => (clone $query)->where('cost_type', CostRecord::TYPE_OTHER)->sum('amount'),
            'count' => $query->count(),
        ];

        // 按产品汇总 Top 10
        $byProduct = (clone $query)->selectRaw('product_id, SUM(amount) as total, COUNT(*) as cnt')
            ->with('product:id,name')
            ->groupBy('product_id')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $stats['by_product'] = $byProduct;

        return $this->success($stats);
    }
}
