<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Warehouse extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'warehouses';

    protected $fillable = [
        'code',
        'name',
        'type',
        'address',
        'manager_id',
        'is_default',
        'capacity',
        'status',
    ];

    protected $casts = [
        'capacity' => 'decimal:2',
    ];

    // 仓库类型常量
    const TYPE_NORMAL = 1;       // 正品仓
    const TYPE_DEFECTIVE = 2;   // 次品仓
    const TYPE_RAW = 3;         // 原材料仓
    const TYPE_SEMI = 4;        // 半成品仓
    const TYPE_FINISHED = 5;    // 成品仓

    // 关联库位
    public function locations()
    {
        return $this->hasMany(Location::class);
    }

    // 关联库存
    public function inventories()
    {
        return $this->hasMany(Inventory::class);
    }

    // 关联负责人
    public function manager()
    {
        return $this->belongsTo(Employee::class, 'manager_id');
    }

    // 获取启用的库位
    public function activeLocations()
    {
        return $this->locations()->where('status', 1);
    }

    // 获取默认仓库
    public static function getDefault()
    {
        return self::where('is_default', 1)->first();
    }
}
