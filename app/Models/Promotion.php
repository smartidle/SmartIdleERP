<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    use HasFactory;

    protected $table = 'promotions';

    protected $fillable = [
        'name',
        'type',
        'trigger_type',
        'start_time',
        'end_time',
        'condition_json',
        'reward_json',
        'applicable_products',
        'applicable_customers',
        'applicable_channels',
        'priority',
        'max_usage',
        'used_count',
        'max_per_customer',
        'status',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'condition_json' => 'array',
        'reward_json' => 'array',
        'applicable_products' => 'array',
        'applicable_customers' => 'array',
        'applicable_channels' => 'array',
    ];

    // 活动类型常量
    const TYPE_FULL_REDUCE = 1;    // 满减
    const TYPE_FULL_GIFT = 2;     // 满赠
    const TYPE_DISCOUNT = 3;      // 打折
    const TYPE_FIXED_PRICE = 4;   // 一口价
    const TYPE_BUY_N_GET_M = 5;   // 买N送M

    // 触发类型常量
    const TRIGGER_AUTO = 1;       // 自动应用
    const TRIGGER_CLAIM = 2;      // 需领取

    // 检查是否在有效期
    public function isActive()
    {
        $now = now();
        return $this->status == 1 && $this->start_time <= $now && $this->end_time >= $now;
    }

    // 检查是否可使用
    public function canUse()
    {
        if (!$this->isActive()) {
            return false;
        }
        if ($this->max_usage && $this->used_count >= $this->max_usage) {
            return false;
        }
        return true;
    }
}
