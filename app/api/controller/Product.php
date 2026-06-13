<?php

namespace app\api\controller;

use app\api\BaseController;
use app\model\Product;
use app\model\ProductSku;
use app\model\ProductCategory;
use app\admin\service\InventoryService;

/**
 * API产品接口
 */
class ProductController extends BaseController
{
    /**
     * 获取产品列表（公开接口）
     */
    public function list()
    {
        $page = max(1, intval($this->request->param('page', 1)));
        $pageSize = max(1, min(50, intval($this->request->param('page_size', 20))));

        $query = Product::with(['category'])
            ->where('status', 1)
            ->order('id', 'desc');

        // 关键词搜索
        $keyword = $this->request->param('keyword', '');
        if ($keyword) {
            $query->whereLike('name|brand', "%{$keyword}%");
        }

        // 分类筛选
        $categoryId = $this->request->param('category_id', 0);
        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        $total = $query->count();
        $list = $query->page($page, $pageSize)->select();

        // 获取库存信息
        $inventoryService = new InventoryService();
        foreach ($list as &$product) {
            $product->skus = ProductSku::where('product_id', $product->id)
                ->where('status', 1)
                ->select();
            foreach ($product->skus as &$sku) {
                $stockInfo = $inventoryService->getSkuStock($sku->id);
                $sku->stock = $stockInfo['available_quantity'];
            }
        }

        return $this->success([
            'list' => $list,
            'total' => $total,
            'page' => $page,
            'page_size' => $pageSize,
        ]);
    }

    /**
     * 获取产品详情
     */
    public function detail()
    {
        $id = $this->request->param('id', 0);

        $product = Product::with(['category'])->find($id);
        if (!$product || $product->status != 1) {
            return $this->error('产品不存在');
        }

        // 获取SKU列表
        $product->skus = ProductSku::where('product_id', $id)
            ->where('status', 1)
            ->select();

        // 获取规格信息
        $product->specs = \app\model\ProductSpec::where('product_id', $id)
            ->order('sort', 'asc')
            ->select();

        return $this->success($product);
    }

    /**
     * 获取分类列表
     */
    public function categories()
    {
        $categories = ProductCategory::getTree();
        return $this->success($categories);
    }

    /**
     * 获取SKU库存
     */
    public function skuStock()
    {
        $skuId = $this->request->param('sku_id', 0);
        if (!$skuId) {
            return $this->error('请选择SKU');
        }

        $inventoryService = new InventoryService();
        $stockInfo = $inventoryService->getSkuStock($skuId);

        return $this->success($stockInfo);
    }
}
