<?php

namespace app\model;

/**
 * 物料齐套模型
 */
class MaterialKitting extends \think\Model
{
    // 表名
    protected $name = 'material_kitting';

    // 自动时间戳
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';

    // 类型转换
    protected $type = [
        'work_order_id' => 'integer',
        'warehouse_id' => 'integer',
        'status' => 'integer',
        'employee_id' => 'integer',
    ];

    // 齐套状态常量
    const STATUS_PENDING = 0;   // 待检查
    const STATUS_KITTING = 1;   // 齐套
    const STATUS_SHORTAGE = 2; // 缺料

    /**
     * 工单关联
     */
    public function workOrder()
    {
        return $this->belongsTo(WorkOrder::class, 'work_order_id');
    }

    /**
     * 仓库
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    /**
     * 检查人
     */
    public function checker()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    /**
     * 生成齐套单号
     */
    public static function generateKittingNo(): string
    {
        $prefix = 'MK';
        $date = date('Ymd');
        $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        return $prefix . $date . $random;
    }
}
