<?php

namespace app\model;

/**
 * 审批记录模型
 */
class ApprovalRecord extends \think\Model
{
    // 表名
    protected $name = 'approval_record';

    // 自动时间戳
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';

    // 类型转换
    protected $type = [
        'instance_id' => 'integer',
        'node_id' => 'integer',
        'approver_id' => 'integer',
        'action' => 'integer',
    ];

    // 审批动作常量
    const ACTION_APPROVE = 1;      // 通过
    const ACTION_REJECT = 2;       // 驳回
    const ACTION_TRANSFER = 3;       // 转审
    const ACTION_WITHDRAW = 4;      // 撤回

    /**
     * 审批实例
     */
    public function instance()
    {
        return $this->belongsTo(ApprovalInstance::class, 'instance_id');
    }

    /**
     * 节点
     */
    public function node()
    {
        return $this->belongsTo(ApprovalNode::class, 'node_id');
    }

    /**
     * 审批人
     */
    public function approver()
    {
        return $this->belongsTo(Employee::class, 'approver_id');
    }
}
