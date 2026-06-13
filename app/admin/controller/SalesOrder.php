<?php

namespace app\admin\controller;

use app\admin\BaseController;
use app\admin\service\SalesOrderService;
use app\model\SalesOrder;
use app\model\SalesOrderItem;
use think\facade\Db;

/**
 * 销售订单控制器
 */
class SalesOrderController extends BaseController
{
    protected SalesOrderService $salesOrderService;

    public function __construct()
    {
        parent::__construct(app);
        $this->salesOrderService = new SalesOrderService();
    }

    /**
     * 订单列表
     */
    public function list()
    {
        [$page, $pageSize] = $this->getPageParams();
        [$order, $sort] = $this->getSortParams();

        $filter = [
            'status' => $this->param('status', ''),
            'customer_id' => $this->param('customer_id', 0),
            'keyword' => $this->param('keyword', ''),
            'date_from' => $this->param('date_from', ''),
            'date_to' => $this->param('date_to', ''),
        ];

        $result = $this->salesOrderService->getOrderList($filter, $page, $pageSize);

        return $this->paginate($result['list'], $result['total'], $page, $pageSize);
    }

    /**
     * 获取订单详情
     */
    public function detail()
    {
        $id = $this->param('id', 0);

        $order = SalesOrder::with(['customer', 'employee', 'warehouse', 'approver', 'items.product', 'items.sku'])
            ->find($id);

        if (!$order) {
            return $this->error('订单不存在');
        }

        // 获取促销信息
        $order->promotions = Db::name('order_promotion')
            ->alias('op')
            ->leftJoin('promotion p', 'p.id = op.promotion_id')
            ->leftJoin('coupon c', 'c.id = op.coupon_id')
            ->where('op.order_id', $id)
            ->select()
            ->toArray();

        // 获取发货记录
        $order->deliveries = Db::name('sales_delivery')
            ->where('order_id', $id)
            ->select()
            ->toArray();

        return $this->success($order);
    }

    /**
     * 创建订单
     */
    public function create()
    {
        $data = $this->param();

        // 验证必填字段
        if (empty($data['customer_id'])) {
            return $this->error('请选择客户');
        }
        if (empty($data['warehouse_id'])) {
            return $this->error('请选择发货仓库');
        }
        if (empty($data['items']) || !is_array($data['items'])) {
            return $this->error('请添加订单明细');
        }

        try {
            $employeeId = $this->request->employeeId ?? 0;
            $order = $this->salesOrderService->createOrder($data, $data['items'], $employeeId);

            return $this->success($order, '创建成功');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 更新订单
     */
    public function update()
    {
        $id = $this->param('id');
        $order = SalesOrder::find($id);

        if (!$order) {
            return $this->error('订单不存在');
        }

        if (!$order->isEditable()) {
            return $this->error('当前状态不能编辑');
        }

        Db::startTrans();
        try {
            // 更新基本信息
            $order->customer_id = $this->param('customer_id', $order->customer_id);
            $order->warehouse_id = $this->param('warehouse_id', $order->warehouse_id);
            $order->delivery_date = $this->param('delivery_date', $order->delivery_date);
            $order->shipping_contact = $this->param('shipping_contact', $order->shipping_contact);
            $order->shipping_phone = $this->param('shipping_phone', $order->shipping_phone);
            $order->shipping_address = $this->param('shipping_address', $order->shipping_address);
            $order->shipping_fee = $this->param('shipping_fee', $order->shipping_fee);
            $order->notes = $this->param('notes', $order->notes);
            $order->save();

            // 如果有更新明细
            if (!empty($data['items'])) {
                // 删除原有明细
                SalesOrderItem::where('order_id', $id)->delete();

                // 重新创建明细
                $customer = \app\model\Customer::find($order->customer_id);
                $subtotal = 0;
                foreach ($data['items'] as $itemData) {
                    $item = $this->createOrderItem($order, $itemData, $customer);
                    $subtotal += $item->subtotal;
                }

                $order->subtotal = $subtotal;
                $order->total_amount = $subtotal - $order->discount_amount + $order->tax_amount + $order->shipping_fee;
                $order->save();
            }

            Db::commit();
            return $this->success($order, '更新成功');
        } catch (\Exception $e) {
            Db::rollback();
            return $this->error($e->getMessage());
        }
    }

    /**
     * 创建订单明细
     */
    protected function createOrderItem($order, $itemData, $customer)
    {
        $product = \app\model\Product::find($itemData['product_id']);
        $sku = !empty($itemData['sku_id']) ? \app\model\ProductSku::find($itemData['sku_id']) : null;

        $unitPrice = $itemData['unit_price'] ?? 0;
        if ($unitPrice <= 0) {
            $specialPrice = $customer->getSpecialPrice($itemData['product_id'], $itemData['sku_id'] ?? 0);
            if ($specialPrice !== null) {
                $unitPrice = $specialPrice;
            } elseif ($sku) {
                $unitPrice = $sku->getEffectiveSalePrice();
            } else {
                $unitPrice = $product->base_sale_price;
            }
        }

        $discountRate = $itemData['discount_rate'] ?? $customer->discount_rate;
        if ($discountRate > 0) {
            $unitPrice = $unitPrice * (1 - $discountRate / 100);
        }

        $item = new SalesOrderItem();
        $item->order_id = $order->id;
        $item->product_id = $itemData['product_id'];
        $item->sku_id = $itemData['sku_id'] ?? 0;
        $item->product_name = $product->name;
        $item->sku_code = $sku ? $sku->sku_code : '';
        $item->spec = $sku ? $sku->spec_combination : [];
        $item->quantity = $itemData['quantity'];
        $item->unit = $itemData['unit'] ?? $product->base_unit;
        $item->unit_price = round($unitPrice, 2);
        $item->cost_price = $sku ? $sku->getEffectiveCostPrice() : $product->base_cost_price;
        $item->tax_rate = $itemData['tax_rate'] ?? 0;
        $item->discount_rate = $discountRate;
        $item->subtotal = round($itemData['quantity'] * $unitPrice, 2);
        $item->save();

        return $item;
    }

    /**
     * 提交审批
     */
    public function submit()
    {
        $id = $this->param('id');
        $employeeId = $this->request->employeeId ?? 0;

        try {
            $order = $this->salesOrderService->submitForApproval($id, $employeeId);
            return $this->success($order, '提交成功');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 审批通过
     */
    public function approve()
    {
        $id = $this->param('id');
        $approverId = $this->request->employeeId ?? 0;

        try {
            $order = $this->salesOrderService->approve($id, $approverId);
            return $this->success($order, '审批通过');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 审批拒绝
     */
    public function reject()
    {
        $id = $this->param('id');
        $reason = $this->param('reason', '');
        $approverId = $this->request->employeeId ?? 0;

        try {
            $order = $this->salesOrderService->reject($id, $approverId, $reason);
            return $this->success($order, '已拒绝');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 取消订单
     */
    public function cancel()
    {
        $id = $this->param('id');
        $reason = $this->param('reason', '');
        $employeeId = $this->request->employeeId ?? 0;

        try {
            $order = $this->salesOrderService->cancel($id, $employeeId, $reason);
            return $this->success($order, '取消成功');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 删除订单（草稿状态）
     */
    public function delete()
    {
        $id = $this->param('id');
        $order = SalesOrder::find($id);

        if (!$order) {
            return $this->error('订单不存在');
        }

        if ($order->status !== SalesOrder::STATUS_DRAFT) {
            return $this->error('只有草稿状态的订单可以删除');
        }

        Db::startTrans();
        try {
            // 删除明细
            SalesOrderItem::where('order_id', $id)->delete();
            // 删除订单
            $order->delete();

            Db::commit();
            return $this->success(null, '删除成功');
        } catch (\Exception $e) {
            Db::rollback();
            return $this->error($e->getMessage());
        }
    }
}
