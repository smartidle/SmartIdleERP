<?php

namespace app\admin\service;

use app\model\Customer;
use app\model\SalesOrder;
use app\model\SalesOrderItem;
use app\model\Product;
use app\model\ProductSku;
use app\model\Warehouse;
use think\facade\Db;

/**
 * 销售订单服务
 */
class SalesOrderService
{
    protected InventoryService $inventoryService;
    protected PromotionService $promotionService;

    public function __construct()
    {
        $this->inventoryService = new InventoryService();
        $this->promotionService = new PromotionService();
    }

    /**
     * 创建销售订单
     *
     * @param array $data 订单数据
     * @param array $items 订单明细
     * @param int $employeeId 操作人ID
     * @return SalesOrder
     */
    public function createOrder(array $data, array $items, int $employeeId): SalesOrder
    {
        Db::startTrans();
        try {
            // 生成订单编号
            $orderNo = SalesOrder::generateOrderNo();

            // 验证客户
            $customer = Customer::find($data['customer_id']);
            if (!$customer) {
                throw new \Exception('客户不存在');
            }

            // 验证仓库
            $warehouse = Warehouse::find($data['warehouse_id']);
            if (!$warehouse) {
                throw new \Exception('仓库不存在');
            }

            // 创建订单
            $order = new SalesOrder();
            $order->order_no = $orderNo;
            $order->customer_id = $data['customer_id'];
            $order->quote_id = $data['quote_id'] ?? 0;
            $order->source = $data['source'] ?? SalesOrder::SOURCE_MANUAL;
            $order->order_date = $data['order_date'] ?? date('Y-m-d');
            $order->delivery_date = $data['delivery_date'] ?? null;
            $order->warehouse_id = $data['warehouse_id'];
            $order->status = SalesOrder::STATUS_DRAFT;
            $order->shipping_contact = $data['shipping_contact'] ?? '';
            $order->shipping_phone = $data['shipping_phone'] ?? '';
            $order->shipping_address = $data['shipping_address'] ?? '';
            $order->shipping_fee = $data['shipping_fee'] ?? 0;
            $order->notes = $data['notes'] ?? '';
            $order->employee_id = $employeeId;
            $order->save();

            // 处理订单明细
            $subtotal = 0;
            foreach ($items as $itemData) {
                $item = $this->createOrderItem($order, $itemData, $customer);
                $subtotal += $item->subtotal;
            }

            // 更新订单金额
            $order->subtotal = $subtotal;
            $order->total_amount = $subtotal + $order->tax_amount - $order->discount_amount + $order->shipping_fee;
            $order->save();

            Db::commit();
            return $order;
        } catch (\Exception $e) {
            Db::rollback();
            throw $e;
        }
    }

    /**
     * 创建订单明细
     *
     * @param SalesOrder $order 订单
     * @param array $itemData 明细数据
     * @param Customer $customer 客户
     * @return SalesOrderItem
     */
    protected function createOrderItem(SalesOrder $order, array $itemData, Customer $customer): SalesOrderItem
    {
        $skuId = $itemData['sku_id'] ?? 0;
        $productId = $itemData['product_id'];
        $quantity = $itemData['quantity'];

        // 获取产品信息
        $product = Product::find($productId);
        if (!$product) {
            throw new \Exception('产品不存在');
        }

        // 获取SKU信息
        $sku = null;
        $skuCode = '';
        $spec = [];
        if ($skuId > 0) {
            $sku = ProductSku::find($skuId);
            if (!$sku) {
                throw new \Exception('SKU不存在');
            }
            $skuCode = $sku->sku_code;
            $spec = $sku->spec_combination;
        }

        // 获取价格（优先客户专属价）
        $unitPrice = $itemData['unit_price'] ?? 0;
        if ($unitPrice <= 0) {
            $specialPrice = $customer->getSpecialPrice($productId, $skuId);
            if ($specialPrice !== null) {
                $unitPrice = $specialPrice;
            } elseif ($sku) {
                $unitPrice = $sku->getEffectiveSalePrice();
            } else {
                $unitPrice = $product->base_sale_price;
            }
        }

        // 应用客户折扣率
        $discountRate = $itemData['discount_rate'] ?? $customer->discount_rate;
        if ($discountRate > 0) {
            $unitPrice = $unitPrice * (1 - $discountRate / 100);
        }

        // 计算小计
        $subtotal = $quantity * $unitPrice;

        // 获取成本价（快照）
        $costPrice = 0;
        if ($sku) {
            $costPrice = $sku->getEffectiveCostPrice();
        } else {
            $costPrice = $product->base_cost_price;
        }

        $item = new SalesOrderItem();
        $item->order_id = $order->id;
        $item->product_id = $productId;
        $item->sku_id = $skuId;
        $item->product_name = $product->name;
        $item->sku_code = $skuCode;
        $item->spec = $spec;
        $item->quantity = $quantity;
        $item->unit = $itemData['unit'] ?? $product->base_unit;
        $item->unit_price = round($unitPrice, 2);
        $item->cost_price = $costPrice;
        $item->tax_rate = $itemData['tax_rate'] ?? 0;
        $item->discount_rate = $discountRate;
        $item->subtotal = round($subtotal, 2);
        $item->delivered_qty = 0;
        $item->save();

        return $item;
    }

    /**
     * 提交流单审批
     *
     * @param int $orderId 订单ID
     * @param int $employeeId 操作人ID
     * @return SalesOrder
     */
    public function submitForApproval(int $orderId, int $employeeId): SalesOrder
    {
        $order = SalesOrder::find($orderId);
        if (!$order) {
            throw new \Exception('订单不存在');
        }

        if ($order->status !== SalesOrder::STATUS_DRAFT) {
            throw new \Exception('当前状态不能提交审批');
        }

        // 验证库存是否足够
        $this->validateStock($order);

        $order->status = SalesOrder::STATUS_PENDING;
        $order->save();

        return $order;
    }

    /**
     * 审批通过
     *
     * @param int $orderId 订单ID
     * @param int $approverId 审批人ID
     * @return SalesOrder
     */
    public function approve(int $orderId, int $approverId): SalesOrder
    {
        $order = SalesOrder::find($orderId);
        if (!$order) {
            throw new \Exception('订单不存在');
        }

        if ($order->status !== SalesOrder::STATUS_PENDING) {
            throw new \Exception('当前状态不能审批');
        }

        // 锁定库存
        $this->lockStock($order);

        $order->status = SalesOrder::STATUS_APPROVED;
        $order->approver_id = $approverId;
        $order->approved_at = time();
        $order->save();

        return $order;
    }

    /**
     * 审批拒绝
     *
     * @param int $orderId 订单ID
     * @param int $approverId 审批人ID
     * @param string $reason 拒绝原因
     * @return SalesOrder
     */
    public function reject(int $orderId, int $approverId, string $reason = ''): SalesOrder
    {
        $order = SalesOrder::find($orderId);
        if (!$order) {
            throw new \Exception('订单不存在');
        }

        if ($order->status !== SalesOrder::STATUS_PENDING) {
            throw new \Exception('当前状态不能审批');
        }

        $order->status = SalesOrder::STATUS_DRAFT;
        $order->notes = ($order->notes ? $order->notes . "\n" : '') . "审批拒绝：" . $reason;
        $order->save();

        return $order;
    }

    /**
     * 取消订单
     *
     * @param int $orderId 订单ID
     * @param int $employeeId 操作人ID
     * @param string $reason 取消原因
     * @return SalesOrder
     */
    public function cancel(int $orderId, int $employeeId, string $reason = ''): SalesOrder
    {
        $order = SalesOrder::find($orderId);
        if (!$order) {
            throw new \Exception('订单不存在');
        }

        if (!$order->isCancellable()) {
            throw new \Exception('当前状态不能取消');
        }

        // 如果已锁定库存，需要解锁
        if (in_array($order->status, [SalesOrder::STATUS_APPROVED, SalesOrder::STATUS_PENDING])) {
            $this->unlockStock($order);
        }

        $order->status = SalesOrder::STATUS_CANCELLED;
        $order->notes = ($order->notes ? $order->notes . "\n" : '') . "取消原因：" . $reason;
        $order->save();

        return $order;
    }

    /**
     * 验证库存
     *
     * @param SalesOrder $order 订单
     * @throws \Exception
     */
    protected function validateStock(SalesOrder $order): void
    {
        $inventoryService = new InventoryService();

        foreach ($order->items as $item) {
            $stockInfo = $inventoryService->getSkuStock($item->sku_id, $order->warehouse_id);
            if ($stockInfo['available_quantity'] < $item->quantity) {
                throw new \Exception("SKU {$item->sku_code} 库存不足，需要：{$item->quantity}，可用：{$stockInfo['available_quantity']}");
            }
        }
    }

    /**
     * 锁定库存
     *
     * @param SalesOrder $order 订单
     * @throws \Exception
     */
    protected function lockStock(SalesOrder $order): void
    {
        foreach ($order->items as $item) {
            $this->inventoryService->lockStock(
                $item->sku_id,
                $item->quantity,
                $order->warehouse_id,
                '订单预占：' . $order->order_no,
                $order->id
            );
        }
    }

    /**
     * 解锁库存
     *
     * @param SalesOrder $order 订单
     */
    protected function unlockStock(SalesOrder $order): void
    {
        foreach ($order->items as $item) {
            try {
                $this->inventoryService->unlockStock(
                    $item->sku_id,
                    $item->quantity,
                    $order->warehouse_id
                );
            } catch (\Exception $e) {
                // 记录错误但不中断流程
            }
        }
    }

    /**
     * 获取订单列表
     *
     * @param array $filter 筛选条件
     * @param int $page 页码
     * @param int $pageSize 每页数量
     * @return array
     */
    public function getOrderList(array $filter, int $page = 1, int $pageSize = 20): array
    {
        $query = SalesOrder::with(['customer', 'employee', 'warehouse'])
            ->order('id', 'desc');

        // 状态筛选
        if (isset($filter['status']) && $filter['status'] !== '') {
            $query->where('status', $filter['status']);
        }

        // 客户筛选
        if (!empty($filter['customer_id'])) {
            $query->where('customer_id', $filter['customer_id']);
        }

        // 日期范围
        if (!empty($filter['date_from'])) {
            $query->where('order_date', '>=', $filter['date_from']);
        }
        if (!empty($filter['date_to'])) {
            $query->where('order_date', '<=', $filter['date_to']);
        }

        // 关键词搜索
        if (!empty($filter['keyword'])) {
            $query->whereLike('order_no|shipping_contact|shipping_phone', "%{$filter['keyword']}%");
        }

        $total = $query->count();
        $list = $query->page($page, $pageSize)->select();

        return [
            'total' => $total,
            'list' => $list,
            'page' => $page,
            'page_size' => $pageSize,
        ];
    }
}
