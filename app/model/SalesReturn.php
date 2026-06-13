<?php

namespace app\model;

use think\Model;

/**
 * 销售退货模型
 */
class SalesReturn extends Model
{
    // 表名
    protected $name = 'sales_return';

    // 自动时间戳
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    // 类型转换
    protected $type = [
        'order_id' => 'integer',
        'customer_id' => 'integer',
        'return_type' => 'integer',
        'status' => 'integer',
        'warehouse_id' => 'integer',
        'employee_id' => 'integer',
        'approved_by' => 'integer',
        'amount' => 'float',
        'quantity' => 'float',
        'refund_method' => 'integer',
    ];

    // 退货类型常量
    const TYPE_RETURN_REFUND = 1;   // 退货退款
    const TYPE_REFUND_ONLY = 2;    // 仅退款
    const TYPE_EXCHANGE = 3;       // 换货

    // 退货状态常量
    const STATUS_APPLYING = 0;     // 申请中
    const STATUS_APPROVED = 1;     // 已审批
    const STATUS_RECEIVED = 2;     // 已收货
    const STATUS_REFUNDED = 3;     // 已退款
    const STATUS_REJECTED = 4;     // 已拒绝
    const STATUS_CANCELLED = 5;    // 已取消
    const STATUS_EXCHANGE_SENT = 6;// 换货发出

    /**
     * 订单关联
     */
    public function order()
    {
        return $this->belongsTo(SalesOrder::class, 'order_id');
    }

    /**
     * 客户关联
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /**
     * 仓库关联
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    /**
     * 处理人
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
        return $this->belongsTo(Employee::class, 'approved_by');
    }

    /**
     * 退货明细
     */
    public function items()
    {
        return $this->hasMany(SalesReturnItem::class, 'return_id');
    }

    /**
     * 退款记录
     */
    public function refunds()
    {
        return $this->hasMany(RefundRecord::class, 'return_id');
    }

    /**
     * 退货入库单
     */
    public function returnDelivery()
    {
        return $this->hasOne(SalesReturnDelivery::class, 'return_id');
    }

    /**
     * 获取状态名称
     */
    public function getStatusName(): string
    {
        $statuses = [
            self::STATUS_APPLYING => '申请中',
            self::STATUS_APPROVED => '已审批',
            self::STATUS_RECEIVED => '已收货',
            self::STATUS_REFUNDED => '已退款',
            self::STATUS_REJECTED => '已拒绝',
            self::STATUS_CANCELLED => '已取消',
            self::STATUS_EXCHANGE_SENT => '换货发出',
        ];

        return $statuses[$this->status] ?? '未知';
    }

    /**
     * 获取退货类型名称
     */
    public function getReturnTypeName(): string
    {
        $types = [
            self::TYPE_RETURN_REFUND => '退货退款',
            self::TYPE_REFUND_ONLY => '仅退款',
            self::TYPE_EXCHANGE => '换货',
        ];

        return $types[$this->return_type] ?? '未知';
    }

    /**
     * 生成退货单号
     */
    public static function generateReturnNo(): string
    {
        $prefix = 'SR';
        $date = date('Ymd');
        $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);

        return $prefix . $date . $random;
    }
}
