<?php

namespace app\model;

use think\Model;

/**
 * 客户地址模型
 */
class CustomerAddress extends Model
{
    // 表名
    protected $name = 'customer_address';

    // 自动时间戳
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';

    // 类型转换
    protected $type = [
        'customer_id' => 'integer',
        'is_default' => 'integer',
    ];

    /**
     * 客户关联
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /**
     * 保存前检查并更新默认地址
     */
    public function before_save()
    {
        if ($this->is_default) {
            // 将其他地址设为非默认
            self::where('customer_id', $this->customer_id)
                ->where('id', '<>', $this->id ?: 0)
                ->update(['is_default' => 0]);
        }
    }
}
