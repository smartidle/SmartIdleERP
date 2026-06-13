<?php

namespace app\model;

use think\Model;

/**
 * 库位模型
 */
class Location extends Model
{
    // 表名
    protected $name = 'location';

    // 自动时间戳
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';

    // 类型转换
    protected $type = [
        'warehouse_id' => 'integer',
        'layer' => 'integer',
        'status' => 'integer',
    ];

    /**
     * 仓库关联
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    /**
     * 库存列表
     */
    public function inventories()
    {
        return $this->hasMany(Inventory::class, 'location_id');
    }

    /**
     * 获取库位完整编码
     */
    public function getFullCode(): string
    {
        $warehouse = $this->warehouse;
        return $warehouse ? $warehouse->code . '-' . $this->code : $this->code;
    }
}
