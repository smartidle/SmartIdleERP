<?php

namespace app\model;

/**
 * 审批节点模型
 */
class ApprovalNode extends \think\Model
{
    // 表名
    protected $name = 'approval_node';

    // 自动时间戳
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';

    // 类型转换
    protected $type = [
        'flow_id' => 'integer',
        'node_type' => 'integer',
        'approver_id' => 'integer',
        'role_id' => 'integer',
    ];

    // 节点类型常量
    const TYPE_APPROVER = 1;     // 指定审批人
    const TYPE_ROLE = 2;          // 指定角色
    const TYPE_CONDITION = 3;     // 条件节点
    const TYPE_END = 4;           // 结束节点

    /**
     * 流程关联
     */
    public function flow()
    {
        return $this->belongsTo(ApprovalFlow::class, 'flow_id');
    }

    /**
     * 审批人
     */
    public function approver()
    {
        return $this->belongsTo(Employee::class, 'approver_id');
    }

    /**
     * 角色
     */
    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }
}
