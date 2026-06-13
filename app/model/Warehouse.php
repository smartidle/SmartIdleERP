<?php

namespace app\model;

use think\Model;

/**
 * 仓库模型
 */
class Warehouse extends Model
{
    // 表名
    protected $name = 'warehouse';

    // 自动时间戳
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';

    // 软删除
    protected $deleteTime = 'delete_time';

    // 类型转换
    protected $type = [
        'manager_id' => 'integer',
        'type' => 'integer',
        'is_default' => 'integer',
        'status' => 'integer',
    ];

    // 仓库类型常量
    const TYPE_NORMAL = 1;        // 正品仓
    const TYPE_DEFECTIVE = 2;    // 次品仓
    const TYPE_MATERIAL = 3;    // 原材料仓
    const TYPE_SEMI = 4;         // 半成品仓
    const TYPE_FINISHED = 5;     // 成品仓

    /**
     * 库位列表
     */
    public function locations()
    {
        return $this->hasMany(Location::class, 'warehouse_id');
    }

    /**
     * 库存列表
     */
    public function inventories()
    {
        return $this->hasMany(Inventory::class, 'warehouse_id');
    }

    /**
     * 负责人
     */
    public function manager()
    {
        return $this->belongsTo(Employee::class, 'manager_id');
    }

    /**
     * 获取默认仓库
     */
    public static function getDefault(): ?Warehouse
    {
        return self::where('is_default', 1)->where('status', 1)->find();
    }

    /**
     * 搜索器：状态筛选
     */
    public function searchStatusAttr($query, $value)
    {
        if ($value !== '') {
            $query->where('status', $value);
        }
    }

    /**
     * 搜索器：类型筛选
     */
    public function searchTypeAttr($query, $value)
    {
        if ($value !== '') {
            $query->where('type', $value);
        }
    }
}
