<?php

namespace app\model;

/**
 * 退货入库单模型
 */
class SalesReturnDelivery extends \think\Model
{
    // 表名
    protected $name = 'sales_return_delivery';

    // 自动时间戳
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';

    // 类型转换
    protected $type = [
        'return_id' => 'integer',
        'warehouse_id' => 'integer',
        'received_by' => 'integer',
        'quality_status' => 'integer',
    ];

    /**
     * 退货单关联
     */
    public function return()
    {
        return $this->belongsTo(SalesReturn::class, 'return_id');
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
     * 退货入库明细
     */
    public function items()
    {
        return $this->hasMany(SalesReturnDeliveryItem::class, 'delivery_id');
    }

    /**
     * 生成入库单号
     */
    public static function generateDeliveryNo(): string
    {
        $prefix = 'RD';
        $date = date('Ymd');
        $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        return $prefix . $date . $random;
    }
}
