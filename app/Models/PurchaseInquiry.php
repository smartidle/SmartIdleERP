<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseInquiry extends Model
{
    protected $table = 'purchase_inquiries';

    protected $fillable = [
        'inquiry_no', 'employee_id', 'expected_date', 'notes', 'status',
    ];

    protected $casts = ['expected_date' => 'date'];

    const STATUS_INQUIRING = 0;
    const STATUS_QUOTED = 1;
    const STATUS_CONVERTED = 2;
    const STATUS_CANCELLED = 3;

    public function items() { return $this->hasMany(PurchaseInquiryItem::class, 'inquiry_id'); }
    public function quotes() { return $this->hasMany(PurchaseInquiryQuote::class, 'inquiry_id'); }
}
