<?php

namespace app\model;

use think\Model;
use think\facade\Db;

/**
 * 销售订单模型
 */
class SalesOrder extends Model
{
    // 表名
    protected $name = 'sales_order';

    // 自动时间戳
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    // 类型转换
    protected $type = [
        'customer_id' => 'integer',
        'quote_id' => 'integer',
        'source' => 'integer',
        'warehouse_id' => 'integer',
        'status' => 'integer',
        'employee_id' => 'integer',
        'approver_id' => 'integer',
        'payment_status' => 'integer',
        'subtotal' => 'float',
        'tax_amount' => 'float',
        'discount_amount' => 'float',
        'promotion_amount' => 'float',
        'coupon_amount' => 'float',
        'total_amount' => 'float',
        'paid_amount' => 'float',
        'shipping_fee' => 'float',
    ];

    // 订单状态常量
    const STATUS_DRAFT = 0;           // 草稿
    const STATUS_PENDING = 1;         // 待审批
    const STATUS_APPROVED = 2;        // 已审批
    const STATUS_PARTIAL_SHIPPED = 3;  // 部分发货
    const STATUS_SHIPPED = 4;         // 已发货
    const STATUS_COMPLETED = 5;       // 已完成
    const STATUS_CANCELLED = 6;       // 已取消

    // 来源常量
    const SOURCE_MANUAL = 1;       // 手工
    const SOURCE_QUOTE = 2;        // 报价转化
    const SOURCE_ECOMMERCE = 3;    // 电商同步

    // 付款状态常量
    const PAY_UNPAID = 0;          // 未付款
    const PAY_PARTIAL = 1;         // 部分付款
    const PAY_PAID = 2;            // 已付清

    /**
     * 客户关联
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /**
     * 报价单关联
     */
    public function quote()
    {
        return $this->belongsTo(SalesQuote::class, 'quote_id');
    }

    /**
     * 仓库关联
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    /**
     * 销售员
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    /**
     * 审批人
     */
    public function approver()
    {
        return $this->belongsTo(Employee::class, 'approver_id');
    }

    /**
     * 订单明细
     */
    public function items()
    {
        return $this->hasMany(SalesOrderItem::class, 'order_id');
    }

    /**
     * 促销记录
     */
    public function promotions()
    {
        return $this->hasMany(OrderPromotion::class, 'order_id');
    }

    /**
     * 发货单
     */
    public function deliveries()
    {
        return $this->hasMany(SalesDelivery::class, 'order_id');
    }

    /**
     * 收款记录
     */
    public function receipts()
    {
        return $this->hasMany(FinanceReceipt::class, 'order_id');
    }

    /**
     * 获取状态名称
     */
    public function getStatusName(): string
    {
        $statuses = [
            self::STATUS_DRAFT => '草稿',
            self::STATUS_PENDING => '待审批',
            self::STATUS_APPROVED => '已审批',
            self::STATUS_PARTIAL_SHIPPED => '部分发货',
            self::STATUS_SHIPPED => '已发货',
            self::STATUS_COMPLETED => '已完成',
            self::STATUS_CANCELLED => '已取消',
        ];

        return $statuses[$this->status] ?? '未知';
    }

    /**
     * 检查是否可编辑
     */
    public function isEditable(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_PENDING]);
    }

    /**
     * 检查是否可取消
     */
    public function isCancellable(): bool
    {
        return in_array($this->status, [
            self::STATUS_DRAFT,
            self::STATUS_PENDING,
            self::STATUS_APPROVED,
        ]);
    }

    /**
     * 计算订单金额
     */
    public function calculateAmounts(): void
    {
        $items = $this->items;
        $subtotal = 0;

        foreach ($items as $item) {
            $subtotal += $item->subtotal;
        }

        $this->subtotal = $subtotal;
        $this->total_amount = $subtotal
            - $this->discount_amount
            - $this->promotion_amount
            - $this->coupon_amount
            + $this->tax_amount
            + $this->shipping_fee;
    }

    /**
     * 获取未发货金额
     */
    public function getUndeliveredAmount(): float
    {
        $deliveredAmount = Db::name('sales_order_item')
            ->where('order_id', $this->id)
            ->sum('delivered_qty * unit_price');

        return $this->total_amount - $deliveredAmount;
    }

    /**
     * 生成订单编号
     */
    public static function generateOrderNo(): string
    {
        $prefix = 'SO';
        $date = date('Ymd');
        $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);

        return $prefix . $date . $random;
    }
}
