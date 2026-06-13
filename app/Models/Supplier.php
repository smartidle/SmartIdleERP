<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'suppliers';

    protected $fillable = [
        'code',
        'name',
        'contact_person',
        'phone',
        'mobile',
        'email',
        'address',
        'country',
        'city',
        'bank_name',
        'bank_account',
        'bank_swift',
        'payment_terms',
        'lead_time',
        'rating',
        'cooperation_status',
        'remark',
        'status',
    ];

    // 关联产品报价
    public function products()
    {
        return $this->hasMany(SupplierProduct::class);
    }

    // 关联采购订单
    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    // 获取首选产品
    public function primaryProducts()
    {
        return $this->products()->where('is_primary', 1);
    }
}
