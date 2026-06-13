<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    use HasFactory;

    protected $table = 'locations';

    protected $fillable = [
        'warehouse_id',
        'code',
        'zone',
        'shelf',
        'layer',
        'position',
        'capacity',
        'status',
    ];

    protected $casts = [
        'layer' => 'integer',
        'capacity' => 'decimal:2',
    ];

    // 关联仓库
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    // 关联库存
    public function inventories()
    {
        return $this->hasMany(Inventory::class);
    }

    // 获取完整库位编码
    public function getFullCodeAttribute()
    {
        $parts = [$this->code];
        if ($this->zone) {
            array_unshift($parts, $this->zone);
        }
        return implode('-', $parts);
    }
}
