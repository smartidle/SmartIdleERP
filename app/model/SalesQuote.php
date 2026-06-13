<?php

namespace app\model;

use think\Model;

/**
 * 销售报价模型
 */
class SalesQuote extends Model
{
    // 表名
    protected $name = 'sales_quote';

    // 自动时间戳
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    // 类型转换
    protected $type = [
        'customer_id' => 'integer',
        'employee_id' => 'integer',
        'status' => 'integer',
        'valid_days' => 'integer',
        'subtotal' => 'float',
        'discount_amount' => 'float',
        'total_amount' => 'float',
    ];

    // 报价单状态常量
    const STATUS_DRAFT = 0;       // 草稿
    const STATUS_SENT = 1;       // 已发送
    const STATUS_CONFIRMED = 2;   // 已确认
    const STATUS_EXPIRED = 3;    // 已失效
    const STATUS_CONVERTED = 4;  // 已转订单

    /**
     * 客户关联
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /**
     * 销售员
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    /**
     * 报价明细
     */
    public function items()
    {
        return $this->hasMany(SalesQuoteItem::class, 'quote_id');
    }

    /**
     * 转化后的订单
     */
    public function order()
    {
        return $this->belongsTo(SalesOrder::class, 'convert_order_id');
    }

    /**
     * 获取状态名称
     */
    public function getStatusName(): string
    {
        $statuses = [
            self::STATUS_DRAFT => '草稿',
            self::STATUS_SENT => '已发送',
            self::STATUS_CONFIRMED => '已确认',
            self::STATUS_EXPIRED => '已失效',
            self::STATUS_CONVERTED => '已转订单',
        ];

        return $statuses[$this->status] ?? '未知';
    }

    /**
     * 检查是否已过期
     */
    public function isExpired(): bool
    {
        $expireTime = $this->create_time + ($this->valid_days * 86400);
        return time() > $expireTime;
    }

    /**
     * 生成报价单号
     */
    public static function generateQuoteNo(): string
    {
        $prefix = 'QT';
        $date = date('Ymd');
        $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);

        return $prefix . $date . $random;
    }

    /**
     * 转换为销售订单
     */
    public function convertToOrder(): SalesOrder
    {
        if ($this->status === self::STATUS_CONVERTED) {
            throw new \Exception('报价单已转换为订单');
        }

        $order = new SalesOrder();
        $order->order_no = SalesOrder::generateOrderNo();
        $order->customer_id = $this->customer_id;
        $order->quote_id = $this->id;
        $order->source = SalesOrder::SOURCE_QUOTE;
        $order->order_date = date('Y-m-d');
        $order->status = SalesOrder::STATUS_DRAFT;
        $order->subtotal = $this->subtotal;
        $order->discount_amount = $this->discount_amount;
        $order->total_amount = $this->total_amount;
        $order->notes = '由报价单' . $this->quote_no . '转化';
        $order->employee_id = $this->employee_id;
        $order->save();

        // 复制明细
        foreach ($this->items as $item) {
            $orderItem = new SalesOrderItem();
            $orderItem->order_id = $order->id;
            $orderItem->product_id = $item->product_id;
            $orderItem->sku_id = $item->sku_id;
            $orderItem->product_name = $item->product_name;
            $orderItem->sku_code = $item->sku_code;
            $orderItem->spec = $item->spec;
            $orderItem->quantity = $item->quantity;
            $orderItem->unit = $item->unit;
            $orderItem->unit_price = $item->unit_price;
            $orderItem->discount_rate = $item->discount_rate;
            $orderItem->subtotal = $item->subtotal;
            $orderItem->save();
        }

        // 更新报价单状态
        $this->status = self::STATUS_CONVERTED;
        $this->convert_order_id = $order->id;
        $this->save();

        return $order;
    }
}
