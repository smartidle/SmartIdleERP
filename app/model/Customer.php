<?php

namespace app\model;

use think\Model;
use think\facade\Db;

/**
 * 客户模型
 */
class Customer extends Model
{
    // 表名
    protected $name = 'customer';

    // 自动时间戳
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    // 软删除
    protected $deleteTime = 'delete_time';

    // 类型转换
    protected $type = [
        'level' => 'integer',
        'status' => 'integer',
        'payment_terms' => 'integer',
        'credit_limit' => 'float',
        'current_debt' => 'float',
        'discount_rate' => 'float',
    ];

    // 客户等级常量
    const LEVEL_NORMAL = 1;    // 普通
    const LEVEL_SILVER = 2;   // 银卡
    const LEVEL_GOLD = 3;     // 金卡
    const LEVEL_DIAMOND = 4;  // 钻石

    /**
     * 地址列表
     */
    public function addresses()
    {
        return $this->hasMany(CustomerAddress::class, 'customer_id');
    }

    /**
     * 专属价格列表
     */
    public function prices()
    {
        return $this->hasMany(CustomerPrice::class, 'customer_id');
    }

    /**
     * 销售订单
     */
    public function orders()
    {
        return $this->hasMany(SalesOrder::class, 'customer_id');
    }

    /**
     * 获取客户专属价格
     */
    public function getSpecialPrice(int $productId, int $skuId = 0): ?float
    {
        $price = Db::name('customer_price')
            ->where('customer_id', $this->id)
            ->where(function ($query) use ($productId, $skuId) {
                $query->whereOr([
                    ['product_id', '=', $productId],
                    ['product_id', '=', 0],
                ]);
            })
            ->where(function ($query) use ($skuId) {
                $query->whereOr([
                    ['sku_id', '=', $skuId],
                    ['sku_id', '=', 0],
                ]);
            })
            ->where(function ($query) {
                $query->whereOr([
                    ['valid_from', '<=', date('Y-m-d')],
                    ['valid_from', '=', null],
                ]);
            })
            ->where(function ($query) {
                $query->whereOr([
                    ['valid_to', '>=', date('Y-m-d')],
                    ['valid_to', '=', null],
                ]);
            })
            ->order('sku_id', 'desc')
            ->find();

        return $price ? (float) $price['price'] : null;
    }

    /**
     * 获取客户的可用优惠券
     */
    public function getAvailableCoupons(): array
    {
        return Db::name('customer_coupon')
            ->alias('cc')
            ->join('coupon c', 'c.id = cc.coupon_id')
            ->where('cc.customer_id', $this->id)
            ->where('cc.status', 1)
            ->where('c.status', 1)
            ->where('c.start_time', '<=', time())
            ->where('c.end_time', '>=', time())
            ->select()
            ->toArray();
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
     * 搜索器：等级筛选
     */
    public function searchLevelAttr($query, $value)
    {
        if ($value !== '') {
            $query->where('level', $value);
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
}
