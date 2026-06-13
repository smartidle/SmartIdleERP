<?php

namespace app\model;

use think\Model;

/**
 * 退款记录模型
 */
class RefundRecord extends Model
{
    // 表名
    protected $name = 'refund_record';

    // 自动时间戳
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';

    // 类型转换
    protected $type = [
        'return_id' => 'integer',
        'order_id' => 'integer',
        'customer_id' => 'integer',
        'amount' => 'float',
        'refund_type' => 'integer',
        'refund_method' => 'integer',
        'status' => 'integer',
    ];

    // 退款类型常量
    const TYPE_REFUND_ONLY = 1;       // 仅退款
    const TYPE_RETURN_REFUND = 2;     // 退货退款
    const TYPE_COMPENSATION = 3;      // 补偿退款

    // 退款方式常量
    const METHOD_ORIGINAL = 1;         // 原路退回
    const METHOD_BALANCE = 2;          // 退到余额
    const METHOD_ACCOUNT = 3;          // 退到账户

    // 状态常量
    const STATUS_PROCESSING = 1;       // 处理中
    const STATUS_SUCCESS = 2;          // 成功
    const STATUS_FAILED = 3;          // 失败

    /**
     * 退货单关联
     */
    public function return()
    {
        return $this->belongsTo(SalesReturn::class, 'return_id');
    }

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
     * 获取状态名称
     */
    public function getStatusName(): string
    {
        $statuses = [
            self::STATUS_PROCESSING => '处理中',
            self::STATUS_SUCCESS => '成功',
            self::STATUS_FAILED => '失败',
        ];

        return $statuses[$this->status] ?? '未知';
    }

    /**
     * 生成退款单号
     */
    public static function generateRefundNo(): string
    {
        $prefix = 'RF';
        $date = date('Ymd');
        $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);

        return $prefix . $date . $random;
    }
}
