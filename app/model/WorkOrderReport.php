<?php

namespace app\model;

/**
 * 生产报工记录模型
 */
class WorkOrderReport extends \think\Model
{
    // 表名
    protected $name = 'work_order_report';

    // 自动时间戳
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';

    // 类型转换
    protected $type = [
        'work_order_id' => 'integer',
        'reporter_id' => 'integer',
        'report_qty' => 'float',
        'qualified_qty' => 'float',
        'defective_qty' => 'float',
    ];

    /**
     * 工单关联
     */
    public function workOrder()
    {
        return $this->belongsTo(WorkOrder::class, 'work_order_id');
    }

    /**
     * 报工人
     */
    public function reporter()
    {
        return $this->belongsTo(Employee::class, 'reporter_id');
    }
}
