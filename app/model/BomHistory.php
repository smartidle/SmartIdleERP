<?php

namespace app\model;

/**
 * BOM变更历史模型
 */
class BomHistory extends \think\Model
{
    // 表名
    protected $name = 'bom_history';

    // 自动时间戳
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';

    // 类型转换
    protected $type = [
        'bom_id' => 'integer',
        'employee_id' => 'integer',
    ];

    /**
     * BOM关联
     */
    public function bom()
    {
        return $this->belongsTo(Bom::class, 'bom_id');
    }

    /**
     * 变更人
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
