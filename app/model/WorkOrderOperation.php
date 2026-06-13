<?php

namespace app\model;

/**
 * 工单工序模型
 */
class WorkOrderOperation extends \think\Model
{
    // 表名
    protected $name = 'work_order_operation';

    // 自动时间戳
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';

    // 类型转换
    protected $type = [
        'work_order_id' => 'integer',
        'worker_id' => 'integer',
        'status' => 'integer',
    ];

    /**
     * 工单关联
     */
    public function workOrder()
    {
        return $this->belongsTo(WorkOrder::class, 'work_order_id');
    }

    /**
     * 作业人员
     */
    public function worker()
    {
        return $this->belongsTo(Employee::class, 'worker_id');
    }
}
