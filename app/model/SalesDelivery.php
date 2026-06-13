<?php

namespace app\model;

use think\Model;

/**
 * 销售发货模型
 */
class SalesDelivery extends Model
{
    // 表名
    protected $name = 'sales_delivery';

    // 自动时间戳
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    // 类型转换
    protected $type = [
        'order_id' => 'integer',
        'warehouse_id' => 'integer',
        'status' => 'integer',
        'is_split' => 'integer',
        'parent_id' => 'integer',
        'shipped_by' => 'integer',
        'weight' => 'float',
        'shipping_fee' => 'float',
    ];

    // 发货单状态常量
    const STATUS_PENDING = 1;       // 待发货
    const STATUS_SHIPPED = 2;      // 已发货
    const STATUS_RECEIVED = 3;     // 已确认收货

    /**
     * 订单关联
     */
    public function order()
    {
        return $this->belongsTo(SalesOrder::class, 'order_id');
    }

    /**
     * 仓库关联
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    /**
     * 发货人
     */
    public function shipper()
    {
        return $this->belongsTo(Employee::class, 'shipped_by');
    }

    /**
     * 发货明细
     */
    public function items()
    {
        return $this->hasMany(SalesDeliveryItem::class, 'delivery_id');
    }

    /**
     * 父发货单（拆单时）
     */
    public function parent()
    {
        return $this->belongsTo(SalesDelivery::class, 'parent_id');
    }

    /**
     * 子发货单（拆单时）
     */
    public function children()
    {
        return $this->hasMany(SalesDelivery::class, 'parent_id');
    }

    /**
     * 获取状态名称
     */
    public function getStatusName(): string
    {
        $statuses = [
            self::STATUS_PENDING => '待发货',
            self::STATUS_SHIPPED => '已发货',
            self::STATUS_RECEIVED => '已确认收货',
        ];

        return $statuses[$this->status] ?? '未知';
    }

    /**
     * 生成发货单号
     */
    public static function generateDeliveryNo(): string
    {
        $prefix = 'SD';
        $date = date('Ymd');
        $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);

        return $prefix . $date . $random;
    }

    /**
     * 确认发货
     */
    public function confirmShip(int $employeeId): void
    {
        if ($this->status !== self::STATUS_PENDING) {
            throw new \Exception('当前状态不能确认发货');
        }

        $this->status = self::STATUS_SHIPPED;
        $this->shipped_by = $employeeId;
        $this->shipped_at = time();
        $this->save();
    }

    /**
     * 确认收货
     */
    public function confirmReceive(): void
    {
        if ($this->status !== self::STATUS_SHIPPED) {
            throw new \Exception('当前状态不能确认收货');
        }

        $this->status = self::STATUS_RECEIVED;
        $this->save();
    }
}
