<?php

namespace app\model;

use think\Model;

/**
 * 采购收货模型
 */
class PurchaseReceive extends Model
{
    // 表名
    protected $name = 'purchase_receive';

    // 自动时间戳
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';

    // 类型转换
    protected $type = [
        'order_id' => 'integer',
        'warehouse_id' => 'integer',
        'status' => 'integer',
        'received_by' => 'integer',
    ];

    // 收货状态常量
    const STATUS_PENDING = 1;      // 待收货
    const STATUS_RECEIVED = 2;     // 已收货
    const STATUS_COMPLETED = 3;   // 已完成

    /**
     * 订单关联
     */
    public function order()
    {
        return $this->belongsTo(PurchaseOrder::class, 'order_id');
    }

    /**
     * 仓库关联
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    /**
     * 收货人
     */
    public function receiver()
    {
        return $this->belongsTo(Employee::class, 'received_by');
    }

    /**
     * 收货明细
     */
    public function items()
    {
        return $this->hasMany(PurchaseReceiveItem::class, 'receive_id');
    }

    /**
     * 生成收货单号
     */
    public static function generateReceiveNo(): string
    {
        $prefix = 'PR';
        $date = date('Ymd');
        $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);

        return $prefix . $date . $random;
    }
}
