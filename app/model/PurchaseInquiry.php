<?php

namespace app\model;

/**
 * 采购询价单模型
 */
class PurchaseInquiry extends \think\Model
{
    // 表名
    protected $name = 'purchase_inquiry';

    // 自动时间戳
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';

    // 类型转换
    protected $type = [
        'employee_id' => 'integer',
        'status' => 'integer',
    ];

    // 询价单状态常量
    const STATUS_INQUIRING = 0;    // 询价中
    const STATUS_QUOTED = 1;       // 报价完成
    const STATUS_CONVERTED = 2;    // 已转订单
    const STATUS_CANCELLED = 3;    // 已取消

    /**
     * 询价人
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    /**
     * 询价明细
     */
    public function items()
    {
        return $this->hasMany(PurchaseInquiryItem::class, 'inquiry_id');
    }

    /**
     * 供应商报价
     */
    public function quotes()
    {
        return $this->hasMany(PurchaseInquiryQuote::class, 'inquiry_id');
    }

    /**
     * 生成询价单号
     */
    public static function generateInquiryNo(): string
    {
        $prefix = 'PI';
        $date = date('Ymd');
        $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        return $prefix . $date . $random;
    }
}
