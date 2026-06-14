<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesQuotation extends Model
{
    protected $table = 'sales_quotations';

    protected $fillable = [
        'quotation_no', 'customer_id', 'contact_id',
        'total_amount', 'discount_rate', 'discount_amount',
        'tax_rate', 'tax_amount', 'final_amount',
        'quotation_date', 'valid_until', 'status',
        'notes', 'terms', 'employee_id',
        'converted_order_id', 'sent_at', 'accepted_at',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'final_amount' => 'decimal:2',
        'status' => 'integer',
    ];

    const STATUS_DRAFT = 1;
    const STATUS_SENT = 2;
    const STATUS_ACCEPTED = 3;
    const STATUS_REJECTED = 4;
    const STATUS_CONVERTED = 5;
    const STATUS_EXPIRED = 6;

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function contact()
    {
        return $this->belongsTo(CustomerContact::class, 'contact_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function convertedOrder()
    {
        return $this->belongsTo(SalesOrder::class, 'converted_order_id');
    }

    public function items()
    {
        return $this->hasMany(\App\Models\SalesQuotationItem::class, 'quotation_id');
    }
}