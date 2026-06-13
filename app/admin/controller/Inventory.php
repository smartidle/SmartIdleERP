<?php

namespace app\admin\controller;

use app\admin\BaseController;
use app\admin\service\InventoryService;
use app\model\Inventory;
use app\model\InventoryLog;
use think\facade\Db;

/**
 * 库存管理控制器
 */
class InventoryController extends BaseController
{
    protected InventoryService $inventoryService;

    public function __construct()
    {
        parent::__construct(app);
        $this->inventoryService = new InventoryService();
    }

    /**
     * 库存列表
     */
    public function list()
    {
        [$page, $pageSize] = $this->getPageParams();
        [$order, $sort] = $this->getSortParams();

        $query = Db::name('inventory i')
            ->join('product p', 'p.id = i.product_id')
            ->join('product_sku ps', 'ps.id = i.sku_id')
            ->join('warehouse w', 'w.id = i.warehouse_id')
            ->field('i.*, p.name as product_name, ps.sku_code, w.name as warehouse_name')
            ->order('i.id', 'desc');

        // 关键词搜索
        $keyword = $this->param('keyword', '');
        if ($keyword) {
            $query->whereLike('p.name|ps.sku_code', "%{$keyword}%");
        }

        // 仓库筛选
        $warehouseId = $this->param('warehouse_id', 0);
        if ($warehouseId) {
            $query->where('i.warehouse_id', $warehouseId);
        }

        // SKU筛选
        $skuId = $this->param('sku_id', 0);
        if ($skuId) {
            $query->where('i.sku_id', $skuId);
        }

        // 只显示有库存的
        $showOnlyStock = $this->param('show_only_stock', false);
        if ($showOnlyStock) {
            $query->where('i.quantity', '>', 0);
        }

        $total = $query->count();
        $list = $query->page($page, $pageSize)->select();

        return $this->paginate($list, $total, $page, $pageSize);
    }

    /**
     * 获取库存详情
     */
    public function detail()
    {
        $id = $this->param('id', 0);

        $inventory = Db::name('inventory i')
            ->join('product p', 'p.id = i.product_id')
            ->join('product_sku ps', 'ps.id = i.sku_id')
            ->join('warehouse w', 'w.id = i.warehouse_id')
            ->leftJoin('location l', 'l.id = i.location_id')
            ->field('i.*, p.name as product_name, ps.sku_code, ps.spec_combination, w.name as warehouse_name, l.code as location_code')
            ->where('i.id', $id)
            ->find();

        if (!$inventory) {
            return $this->error('库存记录不存在');
        }

        // 获取库存流水
        $inventory['logs'] = InventoryLog::where('sku_id', $inventory['sku_id'])
            ->where('warehouse_id', $inventory['warehouse_id'])
            ->order('create_time', 'desc')
            ->limit(20)
            ->select()
            ->toArray();

        return $this->success($inventory);
    }

    /**
     * 库存预警
     */
    public function warning()
    {
        $warnings = $this->inventoryService->checkStockWarning();
        return $this->success($warnings);
    }

    /**
     * 获取SKU库存
     */
    public function skuStock()
    {
        $skuId = $this->param('sku_id', 0);
        $warehouseId = $this->param('warehouse_id', null);

        if (!$skuId) {
            return $this->error('请选择SKU');
        }

        $stockInfo = $this->inventoryService->getSkuStock($skuId, $warehouseId ? (int) $warehouseId : null);

        return $this->success($stockInfo);
    }

    /**
     * 库存流水
     */
    public function log()
    {
        [$page, $pageSize] = $this->getPageParams();

        $query = InventoryLog::with(['product', 'sku', 'warehouse', 'employee'])
            ->order('create_time', 'desc');

        // SKU筛选
        $skuId = $this->param('sku_id', 0);
        if ($skuId) {
            $query->where('sku_id', $skuId);
        }

        // 仓库筛选
        $warehouseId = $this->param('warehouse_id', 0);
        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        // 类型筛选
        $type = $this->param('type', '');
        if ($type !== '') {
            $query->where('type', $type);
        }

        // 时间范围
        $dateFrom = $this->param('date_from', '');
        $dateTo = $this->param('date_to', '');
        if ($dateFrom && $dateTo) {
            $query->whereTime('create_time', 'between', [$dateFrom, $dateTo]);
        }

        $total = $query->count();
        $list = $query->page($page, $pageSize)->select();

        // 添加类型名称
        foreach ($list as &$item) {
            $item->type_name = $item->getTypeName();
        }

        return $this->paginate($list, $total, $page, $pageSize);
    }

    /**
     * 手动调整库存
     */
    public function adjust()
    {
        $skuId = $this->param('sku_id');
        $warehouseId = $this->param('warehouse_id');
        $quantity = $this->param('quantity', 0);
        $reason = $this->param('reason', '');
        $type = $this->param('type', 99); // 其他调整
        $employeeId = $this->request->employeeId ?? 0;

        if (!$skuId || !$warehouseId) {
            return $this->error('参数不完整');
        }

        if ($quantity == 0) {
            return $this->error('调整数量不能为0');
        }

        Db::startTrans();
        try {
            // 查找或创建库存记录
            $inventory = Inventory::where('sku_id', $skuId)
                ->where('warehouse_id', $warehouseId)
                ->find();

            if (!$inventory) {
                // 创建新记录
                $sku = \app\model\ProductSku::find($skuId);
                $inventory = Inventory::create([
                    'product_id' => $sku->product_id,
                    'sku_id' => $skuId,
                    'warehouse_id' => $warehouseId,
                    'quantity' => 0,
                    'locked_quantity' => 0,
                    'cost_price' => 0,
                ]);
            }

            $beforeQty = $inventory->quantity;

            if ($quantity > 0) {
                $inventory->quantity += $quantity;
                $logType = $type == 5 ? 5 : Inventory::TYPE_OTHER; // 盘点盈
            } else {
                if ($inventory->quantity + $quantity < 0) {
                    throw new \Exception('库存不足');
                }
                $inventory->quantity += $quantity;
                $logType = $type == 6 ? 6 : Inventory::TYPE_OTHER; // 盘点亏
            }

            $inventory->save();

            // 记录流水
            InventoryLog::create([
                'product_id' => $inventory->product_id,
                'sku_id' => $skuId,
                'warehouse_id' => $warehouseId,
                'type' => $logType,
                'quantity_before' => $beforeQty,
                'quantity_change' => $quantity,
                'quantity_after' => $inventory->quantity,
                'cost_price' => $inventory->cost_price,
                'reference_type' => 'inventory_adjust',
                'reference_id' => 0,
                'notes' => $reason,
                'employee_id' => $employeeId,
                'create_time' => time(),
            ]);

            Db::commit();
            return $this->success($inventory, '调整成功');
        } catch (\Exception $e) {
            Db::rollback();
            return $this->error($e->getMessage());
        }
    }

    /**
     * 仓库列表
     */
    public function warehouseList()
    {
        $warehouses = Db::name('warehouse')
            ->where('status', 1)
            ->order('is_default', 'desc')
            ->select()
            ->toArray();

        return $this->success($warehouses);
    }
}
