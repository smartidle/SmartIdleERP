<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductSku;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    /**
     * 获取库存列表
     */
    public function index(Request $request)
    {
        $query = Product::with(['category', 'skus']);
        
        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->input('search') . '%');
        }
        
        $products = $query->paginate(20);
        
        return $this->success($products);
    }

    /**
     * 库存预警
     */
    public function warning(Request $request)
    {
        $products = Product::with('skus')
            ->whereNotNull('min_stock')
            ->get()
            ->filter(function ($p) {
                $totalStock = $p->skus->sum('stock');
                return $totalStock < ($p->min_stock ?? 0);
            });
        
        return $this->success([
            'low_stock' => $products->values(),
            'total' => $products->count(),
        ]);
    }

    /**
     * 库存台账
     */
    public function stock(Request $request)
    {
        $products = Product::with('skus')->get();
        
        $totalValue = $products->sum(function ($p) {
            return $p->skus->sum(function ($sku) {
                return ($sku->stock ?? 0) * ($sku->cost_price ?? 0);
            });
        });
        
        return $this->success([
            'products' => $products,
            'total_value' => $totalValue,
        ]);
    }

    /**
     * 库存调整
     */
    public function adjust(Request $request)
    {
        $request->validate([
            'sku_id' => 'required|exists:product_skus,id',
            'new_stock' => 'required|numeric|min:0',
            'reason' => 'nullable|string|max:255',
        ]);
        
        try {
            $sku = ProductSku::find($request->sku_id);
            $sku->stock = $request->new_stock;
            $sku->save();
            
            return $this->success([
                'sku_id' => $sku->id,
                'product_id' => $sku->product_id,
                'new_stock' => $request->new_stock,
            ], 'Stock adjusted');
        } catch (\Exception $e) {
            return $this->error('Failed: ' . $e->getMessage());
        }
    }
}
