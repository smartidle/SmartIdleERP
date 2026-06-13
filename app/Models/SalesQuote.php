<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesQuote extends Model
{
    use HasFactory;

    protected $table = 'sales_quotes';

    protected $fillable = [
        'quote_no',
        'customer_id',
        'employee_id',
        'valid_days',
        'subtotal',
        'discount_amount',
        'total_amount',
        'notes',
        'status',
        'convert_order_id',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'valid_days' => 'integer',
    ];

    // 状态常量
    const STATUS_DRAFT = 0;
    const STATUS_SENT = 1;
    const STATUS_CONFIRMED = 2;
    const STATUS_EXPIRED = 3;
    const STATUS_CONVERTED = 4;

    // 关联客户
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    // 关联销售员
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    // 关联明细
    public function items()
    {
        return $this->hasMany(SalesQuoteItem::class, 'quote_id');
    }

    // 关联转换后的订单
    public function convertedOrder()
    {
        return $this->belongsTo(SalesOrder::class, 'convert_order_id');
    }

    // 获取有效期截止日期
    public function getExpireDateAttribute()
    {
        return $this->created_at->addDays($this->valid_days)->toDateString();
    }

    // 检查是否过期
    public function isExpired()
    {
        return $this->status == self::STATUS_EXPIRED || 
               $this->created_at->addDays($this->valid_days) < now();
    }
}
