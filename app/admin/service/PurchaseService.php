<?php

namespace app\admin\service;

use app\model\PurchaseOrder;
use app\model\PurchaseOrderItem;
use app\model\PurchaseReceive;
use app\model\PurchaseReceiveItem;
use app\model\Supplier;
use app\model\Product;
use app\model\ProductSku;
use app\model\Warehouse;
use think\facade\Db;

/**
 * 采购服务
 */
class PurchaseService
{
    protected InventoryService $inventoryService;

    public function __construct()
    {
        $this->inventoryService = new InventoryService();
    }

    /**
     * 创建采购订单
     *
     * @param array $data 订单数据
     * @param array $items 订单明细
     * @param int $employeeId 操作人ID
     * @return PurchaseOrder
     */
    public function createOrder(array $data, array $items, int $employeeId): PurchaseOrder
    {
        Db::startTrans();
        try {
            $orderNo = PurchaseOrder::generateOrderNo();

            // 验证供应商
            $supplier = Supplier::find($data['supplier_id']);
            if (!$supplier) {
                throw new \Exception('供应商不存在');
            }

            // 验证仓库
            $warehouse = Warehouse::find($data['warehouse_id']);
            if (!$warehouse) {
                throw new \Exception('仓库不存在');
            }

            // 创建订单
            $order = new PurchaseOrder();
            $order->order_no = $orderNo;
            $order->supplier_id = $data['supplier_id'];
            $order->inquiry_id = $data['inquiry_id'] ?? 0;
            $order->order_date = $data['order_date'] ?? date('Y-m-d');
            $order->expected_date = $data['expected_date'] ?? null;
            $order->warehouse_id = $data['warehouse_id'];
            $order->status = PurchaseOrder::STATUS_DRAFT;
            $order->notes = $data['notes'] ?? '';
            $order->employee_id = $employeeId;
            $order->save();

            // 处理订单明细
            $subtotal = 0;
            foreach ($items as $itemData) {
                $item = $this->createOrderItem($order, $itemData);
                $subtotal += $item->subtotal;
            }

            // 更新订单金额
            $order->subtotal = $subtotal;
            $order->total_amount = $subtotal + $order->tax_amount;
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
     */
    protected function createOrderItem(PurchaseOrder $order, array $itemData): PurchaseOrderItem
    {
        $productId = $itemData['product_id'];
        $quantity = $itemData['quantity'];
        $unitPrice = $itemData['unit_price'];

        // 获取产品信息
        $product = Product::find($productId);
        if (!$product) {
            throw new \Exception('产品不存在');
        }

        $skuId = $itemData['sku_id'] ?? 0;
        $sku = null;
        if ($skuId > 0) {
            $sku = ProductSku::find($skuId);
        }

        $subtotal = $quantity * $unitPrice;
        $taxAmount = $subtotal * (($itemData['tax_rate'] ?? 0) / 100);

        $item = new PurchaseOrderItem();
        $item->order_id = $order->id;
        $item->product_id = $productId;
        $item->sku_id = $skuId;
        $item->product_name = $product->name;
        $item->quantity = $quantity;
        $item->unit_price = $unitPrice;
        $item->tax_rate = $itemData['tax_rate'] ?? 0;
        $item->subtotal = $subtotal + $taxAmount;
        $item->received_qty = 0;
        $item->save();

        return $item;
    }

    /**
     * 确认收货
     *
     * @param int $orderId 采购订单ID
     * @param array $receiveData 收货数据
     * @param int $employeeId 操作人ID
     * @return PurchaseReceive
     */
    public function receive(int $orderId, array $receiveData, int $employeeId): PurchaseReceive
    {
        $order = PurchaseOrder::find($orderId);
        if (!$order) {
            throw new \Exception('采购订单不存在');
        }

        if (!in_array($order->status, [PurchaseOrder::STATUS_APPROVED, PurchaseOrder::STATUS_PARTIAL_RECEIVED])) {
            throw new \Exception('当前状态不能收货');
        }

        Db::startTrans();
        try {
            $receiveNo = PurchaseReceive::generateReceiveNo();

            $receive = new PurchaseReceive();
            $receive->receive_no = $receiveNo;
            $receive->order_id = $orderId;
            $receive->warehouse_id = $order->warehouse_id;
            $receive->receive_date = $receiveData['receive_date'] ?? date('Y-m-d');
            $receive->status = PurchaseReceive::STATUS_PENDING;
            $receive->notes = $receiveData['notes'] ?? '';
            $receive->received_by = $employeeId;
            $receive->save();

            // 处理收货明细
            foreach ($receiveData['items'] as $itemData) {
                $this->createReceiveItem($receive, $itemData, $order, $employeeId);
            }

            // 更新订单收货状态
            $this->updateOrderReceiveStatus($order);

            Db::commit();
            return $receive;
        } catch (\Exception $e) {
            Db::rollback();
            throw $e;
        }
    }

    /**
     * 创建收货明细
     */
    protected function createReceiveItem(
        PurchaseReceive $receive,
        array $itemData,
        PurchaseOrder $order,
        int $employeeId
    ): PurchaseReceiveItem {
        $orderItemId = $itemData['order_item_id'];
        $receiveQty = $itemData['receive_qty'];
        $qualifiedQty = $itemData['qualified_qty'] ?? $receiveQty;
        $defectiveQty = $itemData['defective_qty'] ?? 0;

        $orderItem = PurchaseOrderItem::find($orderItemId);
        if (!$orderItem) {
            throw new \Exception('采购订单明细不存在');
        }

        // 入库库存
        $costPrice = $orderItem->unit_price;

        // 入库到正品仓（合格数量）或次品仓（不合格数量）
        $this->inventoryService->addStock(
            $orderItem->sku_id,
            $qualifiedQty,
            $receive->warehouse_id,
            $costPrice,
            $itemData['batch_no'] ?? '',
            'purchase_receive',
            $receive->id,
            $employeeId
        );

        // 更新采购订单明细已收货数量
        $orderItem->received_qty += $receiveQty;
        $orderItem->save();

        $item = new PurchaseReceiveItem();
        $item->receive_id = $receive->id;
        $item->order_item_id = $orderItemId;
        $item->product_id = $orderItem->product_id;
        $item->sku_id = $orderItem->sku_id;
        $item->order_qty = $orderItem->quantity;
        $item->receive_qty = $receiveQty;
        $item->qualified_qty = $qualifiedQty;
        $item->defective_qty = $defectiveQty;
        $item->unit_price = $costPrice;
        $item->batch_no = $itemData['batch_no'] ?? '';
        $item->manufacturing_date = $itemData['manufacturing_date'] ?? null;
        $item->expiry_date = $itemData['expiry_date'] ?? null;
        $item->save();

        return $item;
    }

    /**
     * 更新订单收货状态
     */
    protected function updateOrderReceiveStatus(PurchaseOrder $order): void
    {
        $items = $order->items;
        $allReceived = true;
        $anyReceived = false;

        foreach ($items as $item) {
            if ($item->received_qty < $item->quantity) {
                $allReceived = false;
            }
            if ($item->received_qty > 0) {
                $anyReceived = true;
            }
        }

        if ($allReceived) {
            $order->status = PurchaseOrder::STATUS_RECEIVED;
        } elseif ($anyReceived) {
            $order->status = PurchaseOrder::STATUS_PARTIAL_RECEIVED;
        }

        $order->save();
    }

    /**
     * 审批通过
     */
    public function approve(int $orderId, int $approverId): PurchaseOrder
    {
        $order = PurchaseOrder::find($orderId);
        if (!$order) {
            throw new \Exception('采购订单不存在');
        }

        if ($order->status !== PurchaseOrder::STATUS_PENDING) {
            throw new \Exception('当前状态不能审批');
        }

        $order->status = PurchaseOrder::STATUS_APPROVED;
        $order->approver_id = $approverId;
        $order->approved_at = time();
        $order->save();

        return $order;
    }

    /**
     * 取消订单
     */
    public function cancel(int $orderId, int $employeeId, string $reason = ''): PurchaseOrder
    {
        $order = PurchaseOrder::find($orderId);
        if (!$order) {
            throw new \Exception('采购订单不存在');
        }

        if ($order->status > PurchaseOrder::STATUS_PENDING) {
            throw new \Exception('已审批的订单不能取消');
        }

        $order->status = PurchaseOrder::STATUS_CANCELLED;
        $order->notes = ($order->notes ? $order->notes . "\n" : '') . "取消原因：" . $reason;
        $order->save();

        return $order;
    }
}
