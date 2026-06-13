<?php

namespace app\model;

use think\Model;

/**
 * 采购订单模型
 */
class PurchaseOrder extends Model
{
    // 表名
    protected $name = 'purchase_order';

    // 自动时间戳
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    // 类型转换
    protected $type = [
        'supplier_id' => 'integer',
        'inquiry_id' => 'integer',
        'warehouse_id' => 'integer',
        'status' => 'integer',
        'employee_id' => 'integer',
        'approver_id' => 'integer',
        'payment_status' => 'integer',
        'subtotal' => 'float',
        'tax_amount' => 'float',
        'total_amount' => 'float',
        'paid_amount' => 'float',
    ];

    // 订单状态常量
    const STATUS_DRAFT = 0;           // 草稿
    const STATUS_PENDING = 1;          // 待审批
    const STATUS_APPROVED = 2;         // 已审批
    const STATUS_PARTIAL_RECEIVED = 3; // 部分到货
    const STATUS_RECEIVED = 4;         // 已到货
    const STATUS_COMPLETED = 5;        // 已完成
    const STATUS_CANCELLED = 6;       // 已取消

    // 付款状态常量
    const PAY_UNPAID = 0;          // 未付款
    const PAY_PARTIAL = 1;         // 部分付款
    const PAY_PAID = 2;            // 已付清

    /**
     * 供应商关联
     */
    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    /**
     * 询价单关联
     */
    public function inquiry()
    {
        return $this->belongsTo(PurchaseInquiry::class, 'inquiry_id');
    }

    /**
     * 仓库关联
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    /**
     * 采购员
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
        return $this->hasMany(PurchaseOrderItem::class, 'order_id');
    }

    /**
     * 收货记录
     */
    public function receives()
    {
        return $this->hasMany(PurchaseReceive::class, 'order_id');
    }

    /**
     * 付款记录
     */
    public function payments()
    {
        return $this->hasMany(FinancePayment::class, 'order_id');
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
            self::STATUS_PARTIAL_RECEIVED => '部分到货',
            self::STATUS_RECEIVED => '已到货',
            self::STATUS_COMPLETED => '已完成',
            self::STATUS_CANCELLED => '已取消',
        ];

        return $statuses[$this->status] ?? '未知';
    }

    /**
     * 生成采购单号
     */
    public static function generateOrderNo(): string
    {
        $prefix = 'PO';
        $date = date('Ymd');
        $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);

        return $prefix . $date . $random;
    }
}
