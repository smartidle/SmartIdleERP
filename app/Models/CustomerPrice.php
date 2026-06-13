<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerPrice extends Model
{
    use HasFactory;

    protected $table = 'customer_prices';

    protected $fillable = [
        'customer_id',
        'product_id',
        'sku_id',
        'price',
        'discount_rate',
        'valid_from',
        'valid_to',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'discount_rate' => 'decimal:2',
        'valid_from' => 'date',
        'valid_to' => 'date',
    ];

    // 关联客户
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    // 关联产品
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // 关联SKU
    public function sku()
    {
        return $this->belongsTo(ProductSku::class);
    }

    // 检查是否有效
    public function isValid()
    {
        $now = now()->toDateString();
        if ($this->valid_from && $this->valid_from > $now) {
            return false;
        }
        if ($this->valid_to && $this->valid_to < $now) {
            return false;
        }
        return true;
    }
}
