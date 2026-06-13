<?php

namespace app\admin\controller;

use app\admin\BaseController;
use app\model\Product;
use app\model\ProductSku;
use app\model\ProductCategory;
use app\model\ProductSpec;
use think\facade\Db;

/**
 * 产品管理控制器
 */
class ProductController extends BaseController
{
    /**
     * 产品列表
     */
    public function list()
    {
        [$page, $pageSize] = $this->getPageParams();
        [$order, $sort] = $this->getSortParams();

        $query = Product::with(['category'])
            ->order($order, $sort);

        // 关键词搜索
        $keyword = $this->param('keyword', '');
        if ($keyword) {
            $query->whereLike('name|sku_prefix|brand', "%{$keyword}%");
        }

        // 状态筛选
        $status = $this->param('status', '');
        if ($status !== '') {
            $query->where('status', $status);
        }

        // 分类筛选
        $categoryId = $this->param('category_id', 0);
        if ($categoryId) {
            // 获取该分类及子分类
            $categoryIds = [$categoryId];
            $children = ProductCategory::where('parent_id', $categoryId)->column('id');
            $categoryIds = array_merge($categoryIds, $children);

            $query->whereIn('category_id', $categoryIds);
        }

        $total = $query->count();
        $list = $query->page($page, $pageSize)->select();

        // 获取SKU数量
        foreach ($list as &$item) {
            $item->sku_count = ProductSku::where('product_id', $item->id)->count();
            $item->stock = $item->getAvailableStock();
        }

        return $this->paginate($list, $total, $page, $pageSize);
    }

    /**
     * 获取产品详情
     */
    public function detail()
    {
        $id = $this->param('id', 0);
        $product = Product::with(['category'])->find($id);

        if (!$product) {
            return $this->error('产品不存在');
        }

        // 获取规格信息
        $product->specs = ProductSpec::where('product_id', $id)->order('sort', 'asc')->select();

        // 获取SKU列表
        $product->skus = ProductSku::where('product_id', $id)->select();

        // 获取库存信息
        $inventoryService = new \app\admin\service\InventoryService();
        foreach ($product->skus as &$sku) {
            $sku->stock_info = $inventoryService->getSkuStock($sku->id);
        }

        return $this->success($product);
    }

    /**
     * 创建产品
     */
    public function create()
    {
        $data = $this->param();

        Db::startTrans();
        try {
            // 创建产品
            $product = new Product();
            $product->sku_prefix = $data['sku_prefix'] ?? '';
            $product->name = $data['name'];
            $product->name_i18n = $data['name_i18n'] ?? [];
            $product->category_id = $data['category_id'] ?? 0;
            $product->brand = $data['brand'] ?? '';
            $product->base_unit = $data['base_unit'] ?? '个';
            $product->base_cost_price = $data['base_cost_price'] ?? 0;
            $product->base_sale_price = $data['base_sale_price'] ?? 0;
            $product->base_wholesale_price = $data['base_wholesale_price'] ?? 0;
            $product->weight = $data['weight'] ?? 0;
            $product->length_cm = $data['length_cm'] ?? 0;
            $product->width_cm = $data['width_cm'] ?? 0;
            $product->height_cm = $data['height_cm'] ?? 0;
            $product->volume_m3 = $data['volume_m3'] ?? 0;
            $product->min_stock = $data['min_stock'] ?? 0;
            $product->max_stock = $data['max_stock'] ?? 0;
            $product->shelf_life_days = $data['shelf_life_days'] ?? 0;
            $product->min_pack_qty = $data['min_pack_qty'] ?? 1;
            $product->image = $data['image'] ?? '';
            $product->images = $data['images'] ?? [];
            $product->description = $data['description'] ?? '';
            $product->is_bom = $data['is_bom'] ?? 0;
            $product->has_spec = $data['has_spec'] ?? 0;
            $product->status = $data['status'] ?? 1;
            $product->save();

            // 如果有规格，保存规格定义
            if (!empty($data['specs'])) {
                foreach ($data['specs'] as $specData) {
                    $spec = new ProductSpec();
                    $spec->product_id = $product->id;
                    $spec->spec_name = $specData['spec_name'];
                    $spec->spec_values = $specData['spec_values'];
                    $spec->is_color = $specData['is_color'] ?? 0;
                    $spec->is_size = $specData['is_size'] ?? 0;
                    $spec->sort = $specData['sort'] ?? 0;
                    $spec->save();
                }
            }

            // 如果有SKU数据，直接创建
            if (!empty($data['skus'])) {
                foreach ($data['skus'] as $skuData) {
                    $sku = new ProductSku();
                    $sku->product_id = $product->id;
                    $sku->sku_code = $skuData['sku_code'] ?? ProductSku::generateSkuCode($product->sku_prefix, $skuData['spec_combination'] ?? []);
                    $sku->barcode = $skuData['barcode'] ?? '';
                    $sku->spec_combination = $skuData['spec_combination'] ?? [];
                    $sku->cost_price = $skuData['cost_price'] ?? null;
                    $sku->sale_price = $skuData['sale_price'] ?? null;
                    $sku->wholesale_price = $skuData['wholesale_price'] ?? null;
                    $sku->image = $skuData['image'] ?? '';
                    $sku->weight = $skuData['weight'] ?? null;
                    $sku->status = 1;
                    $sku->save();
                }
            }

            Db::commit();
            return $this->success($product, '创建成功');
        } catch (\Exception $e) {
            Db::rollback();
            return $this->error($e->getMessage());
        }
    }

    /**
     * 更新产品
     */
    public function update()
    {
        $id = $this->param('id');
        $product = Product::find($id);

        if (!$product) {
            return $this->error('产品不存在');
        }

        $data = $this->param();

        Db::startTrans();
        try {
            // 更新产品
            $product->sku_prefix = $data['sku_prefix'] ?? $product->sku_prefix;
            $product->name = $data['name'] ?? $product->name;
            $product->name_i18n = $data['name_i18n'] ?? $product->name_i18n;
            $product->category_id = $data['category_id'] ?? $product->category_id;
            $product->brand = $data['brand'] ?? $product->brand;
            $product->base_unit = $data['base_unit'] ?? $product->base_unit;
            $product->base_cost_price = $data['base_cost_price'] ?? $product->base_cost_price;
            $product->base_sale_price = $data['base_sale_price'] ?? $product->base_sale_price;
            $product->base_wholesale_price = $data['base_wholesale_price'] ?? $product->base_wholesale_price;
            $product->weight = $data['weight'] ?? $product->weight;
            $product->min_stock = $data['min_stock'] ?? $product->min_stock;
            $product->max_stock = $data['max_stock'] ?? $product->max_stock;
            $product->shelf_life_days = $data['shelf_life_days'] ?? $product->shelf_life_days;
            $product->image = $data['image'] ?? $product->image;
            $product->images = $data['images'] ?? $product->images;
            $product->description = $data['description'] ?? $product->description;
            $product->is_bom = $data['is_bom'] ?? $product->is_bom;
            $product->has_spec = $data['has_spec'] ?? $product->has_spec;
            $product->status = $data['status'] ?? $product->status;
            $product->save();

            Db::commit();
            return $this->success($product, '更新成功');
        } catch (\Exception $e) {
            Db::rollback();
            return $this->error($e->getMessage());
        }
    }

    /**
     * 删除产品
     */
    public function delete()
    {
        $id = $this->param('id');
        $product = Product::find($id);

        if (!$product) {
            return $this->error('产品不存在');
        }

        // 检查是否有关联订单
        $hasOrder = Db::name('sales_order_item')->where('product_id', $id)->count() > 0;
        if ($hasOrder) {
            return $this->error('该产品有订单记录，不能删除');
        }

        // 软删除
        $product->delete();

        return $this->success(null, '删除成功');
    }

    /**
     * 生成SKU
     */
    public function generateSku()
    {
        $productId = $this->param('product_id');
        $specCombinations = $this->param('spec_combinations', []);

        $product = Product::find($productId);
        if (!$product) {
            return $this->error('产品不存在');
        }

        // 获取规格定义
        $specs = ProductSpec::where('product_id', $productId)->order('sort', 'asc')->select();

        if ($specs->isEmpty()) {
            return $this->error('该产品没有规格定义');
        }

        // 生成所有规格组合
        $combinations = $this->generateCombinations($specs);

        $skus = [];
        foreach ($combinations as $combination) {
            $skuCode = ProductSku::generateSkuCode($product->sku_prefix, $combination);

            // 检查是否已存在
            $exists = ProductSku::where('product_id', $productId)
                ->where('spec_hash', md5(json_encode($combination, JSON_UNESCAPED_UNICODE)))
                ->find();

            if (!$exists) {
                $sku = new ProductSku();
                $sku->product_id = $productId;
                $sku->sku_code = $skuCode;
                $sku->spec_combination = $combination;
                $sku->cost_price = $product->base_cost_price;
                $sku->sale_price = $product->base_sale_price;
                $sku->status = 1;
                $sku->save();

                $skus[] = $sku;
            }
        }

        return $this->success($skus, '生成成功');
    }

    /**
     * 生成规格组合
     */
    protected function generateCombinations($specs, $index = 0, $current = []): array
    {
        if ($index >= count($specs)) {
            return [$current];
        }

        $spec = $specs[$index];
        $values = $spec->spec_values;
        $result = [];

        foreach ($values as $value) {
            $newCurrent = $current;
            $newCurrent[$spec->spec_name] = $value;
            $result = array_merge($result, $this->generateCombinations($specs, $index + 1, $newCurrent));
        }

        return $result;
    }

    /**
     * 批量更新SKU
     */
    public function batchUpdateSku()
    {
        $skus = $this->param('skus', []);

        Db::startTrans();
        try {
            foreach ($skus as $skuData) {
                $sku = ProductSku::find($skuData['id']);
                if ($sku) {
                    $sku->cost_price = $skuData['cost_price'] ?? $sku->cost_price;
                    $sku->sale_price = $skuData['sale_price'] ?? $sku->sale_price;
                    $sku->wholesale_price = $skuData['wholesale_price'] ?? $sku->wholesale_price;
                    $sku->barcode = $skuData['barcode'] ?? $sku->barcode;
                    $sku->image = $skuData['image'] ?? $sku->image;
                    $sku->status = $skuData['status'] ?? $sku->status;
                    $sku->save();
                }
            }

            Db::commit();
            return $this->success(null, '更新成功');
        } catch (\Exception $e) {
            Db::rollback();
            return $this->error($e->getMessage());
        }
    }

    /**
     * 获取产品分类树
     */
    public function categoryTree()
    {
        $tree = ProductCategory::getTree();
        return $this->success($tree);
    }

    /**
     * 创建分类
     */
    public function createCategory()
    {
        $data = $this->param();

        $category = new ProductCategory();
        $category->parent_id = $data['parent_id'] ?? 0;
        $category->name = $data['name'];
        $category->name_i18n = $data['name_i18n'] ?? [];
        $category->code = $data['code'] ?? '';
        $category->sort = $data['sort'] ?? 0;
        $category->status = $data['status'] ?? 1;
        $category->save();

        // 更新层级
        if ($category->parent_id > 0) {
            $parent = ProductCategory::find($category->parent_id);
            $category->level = $parent->level + 1;
            $category->save();
        }

        return $this->success($category, '创建成功');
    }

    /**
     * 更新分类
     */
    public function updateCategory()
    {
        $id = $this->param('id');
        $category = ProductCategory::find($id);

        if (!$category) {
            return $this->error('分类不存在');
        }

        $data = $this->param();

        $category->name = $data['name'] ?? $category->name;
        $category->name_i18n = $data['name_i18n'] ?? $category->name_i18n;
        $category->code = $data['code'] ?? $category->code;
        $category->sort = $data['sort'] ?? $category->sort;
        $category->status = $data['status'] ?? $category->status;
        $category->save();

        return $this->success($category, '更新成功');
    }

    /**
     * 删除分类
     */
    public function deleteCategory()
    {
        $id = $this->param('id');

        // 检查是否有子分类
        $hasChildren = ProductCategory::where('parent_id', $id)->count() > 0;
        if ($hasChildren) {
            return $this->error('该分类有子分类，不能删除');
        }

        // 检查是否有产品
        $hasProducts = Product::where('category_id', $id)->count() > 0;
        if ($hasProducts) {
            return $this->error('该分类有产品，不能删除');
        }

        ProductCategory::destroy($id);

        return $this->success(null, '删除成功');
    }
}
