<?php

namespace app\model;

/**
 * 生产工单模型
 */
class WorkOrder extends \think\Model
{
    // 表名
    protected $name = 'work_order';

    // 自动时间戳
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    // 类型转换
    protected $type = [
        'bom_id' => 'integer',
        'product_id' => 'integer',
        'sku_id' => 'integer',
        'warehouse_id' => 'integer',
        'planned_qty' => 'float',
        'completed_qty' => 'float',
        'scrap_qty' => 'float',
        'priority' => 'integer',
        'status' => 'integer',
    ];

    // 工单状态常量
    const STATUS_DRAFT = 0;      // 草稿
    const STATUS_PENDING = 1;     // 待审批
    const STATUS_APPROVED = 2;   // 已审批
    const STATUS_PRODUCING = 3;  // 生产中
    const STATUS_COMPLETED = 4;  // 已完工
    const STATUS_STORED = 5;     // 已入库
    const STATUS_CANCELLED = 6;  // 已取消

    /**
     * BOM关联
     */
    public function bom()
    {
        return $this->belongsTo(Bom::class, 'bom_id');
    }

    /**
     * 生产产品
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * 仓库
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    /**
     * 创建人
     */
    public function creator()
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
     * 工序
     */
    public function operations()
    {
        return $this->hasMany(WorkOrderOperation::class, 'work_order_id');
    }

    /**
     * 领料
     */
    public function materials()
    {
        return $this->hasMany(WorkOrderMaterial::class, 'work_order_id');
    }

    /**
     * 报工记录
     */
    public function reports()
    {
        return $this->hasMany(WorkOrderReport::class, 'work_order_id');
    }

    /**
     * 生成工单编号
     */
    public static function generateWoNo(): string
    {
        $prefix = 'WO';
        $date = date('Ymd');
        $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        return $prefix . $date . $random;
    }
}
