<?php

namespace app\model;

/**
 * 审批流程模型
 */
class ApprovalFlow extends \think\Model
{
    // 表名
    protected $name = 'approval_flow';

    // 自动时间戳
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';

    // 类型转换
    protected $type = [
        'is_active' => 'integer',
    ];

    /**
     * 审批节点
     */
    public function nodes()
    {
        return $this->hasMany(ApprovalNode::class, 'flow_id');
    }

    /**
     * 审批实例
     */
    public function instances()
    {
        return $this->hasMany(ApprovalInstance::class, 'flow_id');
    }

    /**
     * 获取有效的流程
     */
    public static function getActiveFlow(string $module): ?ApprovalFlow
    {
        return self::where('module', $module)
            ->where('is_active', 1)
            ->find();
    }
}
