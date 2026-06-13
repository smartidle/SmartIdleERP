<?php

namespace app\model;

/**
 * 生产报废单模型
 */
class WorkOrderScrap extends \think\Model
{
    // 表名
    protected $name = 'work_order_scrap';

    // 自动时间戳
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';

    // 类型转换
    protected $type = [
        'work_order_id' => 'integer',
        'product_id' => 'integer',
        'sku_id' => 'integer',
        'quantity' => 'float',
        'cost_loss' => 'float',
        'employee_id' => 'integer',
    ];

    /**
     * 工单关联
     */
    public function workOrder()
    {
        return $this->belongsTo(WorkOrder::class, 'work_order_id');
    }

    /**
     * 产品
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * 操作人
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    /**
     * 生成报废单号
     */
    public static function generateScrapNo(): string
    {
        $prefix = 'SC';
        $date = date('Ymd');
        $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        return $prefix . $date . $random;
    }
}
