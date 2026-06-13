<?php

namespace app\model;

/**
 * 审批实例模型
 */
class ApprovalInstance extends \think\Model
{
    // 表名
    protected $name = 'approval_instance';

    // 自动时间戳
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    // 类型转换
    protected $type = [
        'flow_id' => 'integer',
        'status' => 'integer',
        'initiator_id' => 'integer',
        'current_node_id' => 'integer',
    ];

    // 审批状态常量
    const STATUS_PENDING = 0;      // 审批中
    const STATUS_APPROVED = 1;    // 已通过
    const STATUS_REJECTED = 2;     // 已驳回
    const STATUS_CANCELLED = 3;    // 已撤回

    /**
     * 流程关联
     */
    public function flow()
    {
        return $this->belongsTo(ApprovalFlow::class, 'flow_id');
    }

    /**
     * 发起人
     */
    public function initiator()
    {
        return $this->belongsTo(Employee::class, 'initiator_id');
    }

    /**
     * 审批记录
     */
    public function records()
    {
        return $this->hasMany(ApprovalRecord::class, 'instance_id');
    }
}
