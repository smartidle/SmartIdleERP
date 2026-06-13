<?php

namespace app\model;

use think\Model;

/**
 * 操作日志模型
 */
class OperationLog extends Model
{
    // 表名
    protected $name = 'operation_log';

    // 自动时间戳
    protected $autoWriteTimestamp = false;
    protected $createTime = 'create_time';

    // 禁止自动写入时间戳
    public $autoWriteTimestamp = false;

    // 类型转换
    protected $type = [
        'employee_id' => 'integer',
        'target_id' => 'integer',
    ];

    /**
     * 操作人
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    /**
     * 记录日志
     */
    public static function log(
        int $employeeId,
        string $module,
        string $action,
        string $targetType = '',
        int $targetId = 0,
        string $description = '',
        string $ip = '',
        string $method = '',
        string $url = '',
        array $params = []
    ): void {
        self::create([
            'employee_id' => $employeeId,
            'module' => $module,
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'description' => $description,
            'ip' => $ip,
            'method' => $method,
            'url' => $url,
            'params' => json_encode($params, JSON_UNESCAPED_UNICODE),
            'create_time' => time(),
        ]);
    }
}
