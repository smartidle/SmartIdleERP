<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'customers';

    protected $fillable = [
        'code',
        'name',
        'level',
        'credit_limit',
        'current_debt',
        'discount_rate',
        'payment_terms',
        'contact_person',
        'phone',
        'mobile',
        'email',
        'address',
        'country',
        'city',
        'tax_number',
        'source',
        'birthday',
        'remark',
        'status',
    ];

    protected $casts = [
        'credit_limit' => 'decimal:2',
        'current_debt' => 'decimal:2',
        'discount_rate' => 'decimal:2',
        'birthday' => 'date',
    ];

    // 客户等级常量
    const LEVEL_NORMAL = 1;
    const LEVEL_SILVER = 2;
    const LEVEL_GOLD = 3;
    const LEVEL_DIAMOND = 4;

    // 关联收货地址
    public function addresses()
    {
        return $this->hasMany(CustomerAddress::class);
    }

    // 关联专属价格
    public function prices()
    {
        return $this->hasMany(CustomerPrice::class);
    }

    // 关联联系记录
    public function contactLogs()
    {
        return $this->hasMany(CustomerContactLog::class);
    }

    // 关联销售订单
    public function salesOrders()
    {
        return $this->hasMany(SalesOrder::class);
    }

    // 关联优惠券
    public function coupons()
    {
        return $this->belongsToMany(Coupon::class, 'customer_coupons')
            ->withPivot(['code', 'status', 'order_id', 'used_at', 'received_at', 'expire_at']);
    }

    // 关联销售报价
    public function salesQuotes()
    {
        return $this->hasMany(SalesQuote::class);
    }

    // 获取默认地址
    public function getDefaultAddressAttribute()
    {
        return $this->addresses()->where('is_default', 1)->first();
    }

    // 获取可用信用额度
    public function getAvailableCreditAttribute()
    {
        return max(0, $this->credit_limit - $this->current_debt);
    }
}
