<?php

namespace app\model;

use think\Model;

/**
 * 促销活动模型
 */
class Promotion extends Model
{
    // 表名
    protected $name = 'promotion';

    // 自动时间戳
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    // 类型转换
    protected $type = [
        'type' => 'integer',
        'trigger_type' => 'integer',
        'start_time' => 'integer',
        'end_time' => 'integer',
        'priority' => 'integer',
        'max_usage' => 'integer',
        'used_count' => 'integer',
        'max_per_customer' => 'integer',
        'status' => 'integer',
    ];

    // 活动类型常量
    const TYPE_FULL_REDUCE = 1;     // 满减
    const TYPE_FULL_GIFT = 2;        // 满赠
    const TYPE_DISCOUNT = 3;         // 打折
    const TYPE_FIXED_PRICE = 4;      // 一口价
    const TYPE_BUY_N_GET_M = 5;      // 买N送M

    // 触发类型常量
    const TRIGGER_AUTO = 1;          // 自动应用
    const TRIGGER_CLAIM = 2;         // 需领取

    /**
     * 触发条件获取器
     */
    public function getConditionJsonAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * 触发条件设置器
     */
    public function setConditionJsonAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    /**
     * 奖励内容获取器
     */
    public function getRewardJsonAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * 奖励内容设置器
     */
    public function setRewardJsonAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    /**
     * 适用范围获取器
     */
    public function getApplicableProductsAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * 适用范围设置器
     */
    public function setApplicableProductsAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    /**
     * 适用客户获取器
     */
    public function getApplicableCustomersAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * 适用客户设置器
     */
    public function setApplicableCustomersAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    /**
     * 检查活动是否有效
     */
    public function isValid(): bool
    {
        if ($this->status != 1) {
            return false;
        }

        $now = time();
        if ($now < $this->start_time || $now > $this->end_time) {
            return false;
        }

        if ($this->max_usage > 0 && $this->used_count >= $this->max_usage) {
            return false;
        }

        return true;
    }

    /**
     * 获取活动类型名称
     */
    public function getTypeName(): string
    {
        $types = [
            self::TYPE_FULL_REDUCE => '满减',
            self::TYPE_FULL_GIFT => '满赠',
            self::TYPE_DISCOUNT => '打折',
            self::TYPE_FIXED_PRICE => '一口价',
            self::TYPE_BUY_N_GET_M => '买N送M',
        ];

        return $types[$this->type] ?? '未知';
    }
}
