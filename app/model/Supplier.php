<?php

namespace app\model;

use think\Model;

/**
 * 供应商模型
 */
class Supplier extends Model
{
    // 表名
    protected $name = 'supplier';

    // 自动时间戳
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    // 软删除
    protected $deleteTime = 'delete_time';

    // 类型转换
    protected $type = [
        'status' => 'integer',
        'rating' => 'integer',
        'payment_terms' => 'integer',
        'lead_time' => 'integer',
    ];

    /**
     * 产品报价列表
     */
    public function products()
    {
        return $this->hasMany(SupplierProduct::class, 'supplier_id');
    }

    /**
     * 采购订单
     */
    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class, 'supplier_id');
    }

    /**
     * 搜索器：关键词搜索
     */
    public function searchKeywordAttr($query, $value)
    {
        if (!empty($value)) {
            $query->whereLike('name|code|contact_person|phone|mobile', "%{$value}%");
        }
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
     * 搜索器：评级筛选
     */
    public function searchRatingAttr($query, $value)
    {
        if ($value !== '') {
            $query->where('rating', '>=', $value);
        }
    }
}
