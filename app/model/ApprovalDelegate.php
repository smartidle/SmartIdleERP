<?php

namespace app\model;

/**
 * 审批委托模型
 */
class ApprovalDelegate extends \think\Model
{
    // 表名
    protected $name = 'approval_delegate';

    // 自动时间戳
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';

    // 类型转换
    protected $type = [
        'delegator_id' => 'integer',
        'delegate_id' => 'integer',
        'status' => 'integer',
    ];

    /**
     * 委托人
     */
    public function delegator()
    {
        return $this->belongsTo(Employee::class, 'delegator_id');
    }

    /**
     * 被委托人
     */
    public function delegate()
    {
        return $this->belongsTo(Employee::class, 'delegate_id');
    }

    /**
     * 检查是否有有效的委托
     */
    public static function hasActiveDelegate(int $delegatorId, string $module = ''): bool
    {
        $now = date('Y-m-d');
        $query = self::where('delegator_id', $delegatorId)
            ->where('status', 1)
            ->where('start_date', '<=', $now)
            ->where('end_date', '>=', $now);

        if ($module) {
            $query->where(function ($q) use ($module) {
                $q->whereOr('module', $module);
                $q->whereOr('module', '');
            });
        }

        return $query->count() > 0;
    }
}
