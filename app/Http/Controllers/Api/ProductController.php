<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductSku;
use App\Models\ProductCategory;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * 显示产品列表
     */
    public function index(Request $request)
    {
        $query = Product::query();

        // 搜索过滤
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku_prefix', 'like', "%{$search}%");
            });
        }

        // 分类过滤
        if ($request->has('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        // 状态过滤
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        // 是否有多规格
        if ($request->has('has_spec')) {
            $query->where('has_spec', $request->input('has_spec'));
        }

        $products = $query->orderBy('id', 'desc')
            ->paginate($request->input('per_page', 20));

        // 获取分类信息单独返回
        $categories = ProductCategory::all(['id', 'name', 'code']);

        return response()->json([
            'success' => true,
            'message' => '获取成功',
            'data' => $products->items(),
            'categories' => $categories,
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
        ]);
    }

    /**
     * 创建产品
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'nullable|exists:product_categories,id',
            'sku_prefix' => 'nullable|string|max:16',
            'base_unit' => 'nullable|string|max:32',
            'base_cost_price' => 'nullable|numeric|min:0',
            'base_sale_price' => 'nullable|numeric|min:0',
            'weight' => 'nullable|numeric|min:0',
            'status' => 'nullable|integer|in:0,1',
        ]);

        $product = Product::create($request->only([
            'name', 'category_id', 'sku_prefix', 'base_unit',
            'base_cost_price', 'base_sale_price', 'weight', 'status',
        ]));

        return $this->success($product, '创建成功', 201);
    }

    /**
     * 显示单个产品
     */
    public function show(Product $product)
    {
        $product->load(['category', 'skus']);
        return $this->success($product);
    }

    /**
     * 更新产品
     */
    public function update(Request $request, Product $product)
    {
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'category_id' => 'nullable|exists:product_categories,id',
            'sku_prefix' => 'nullable|string|max:16',
            'base_unit' => 'nullable|string|max:32',
            'base_cost_price' => 'nullable|numeric|min:0',
            'base_sale_price' => 'nullable|numeric|min:0',
            'weight' => 'nullable|numeric|min:0',
            'status' => 'nullable|integer|in:0,1',
        ]);

        $product->update($request->only([
            'name', 'category_id', 'sku_prefix', 'base_unit',
            'base_cost_price', 'base_sale_price', 'weight', 'status',
        ]));

        return $this->success($product, '更新成功');
    }

    /**
     * 删除产品
     */
    public function destroy(Product $product)
    {
        $product->delete();
        return $this->success(null, '删除成功');
    }

    /**
     * 搜索产品
     */
    public function search(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:1',
        ]);

        $products = Product::where('name', 'like', "%{$request->input('q')}%")
            ->orWhere('sku_prefix', 'like', "%{$request->input('q')}%")
            ->limit(20)
            ->get(['id', 'name', 'sku_prefix', 'base_sale_price']);

        return $this->success($products);
    }

    /**
     * 获取产品SKU列表
     */
    public function skus(Product $product)
    {
        $skus = $product->skus()->get(['id', 'sku_code', 'spec_values', 'cost_price', 'sale_price', 'stock']);
        return $this->success($skus);
    }
}
