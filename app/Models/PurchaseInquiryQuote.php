<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseInquiryQuote extends Model
{
    protected $table = 'purchase_inquiry_quotes';

    protected $fillable = [
        'inquiry_id', 'inquiry_item_id', 'supplier_id', 'product_id', 'sku_id',
        'unit_price', 'delivery_days', 'valid_until', 'is_selected', 'notes',
    ];

    protected $casts = ['valid_until' => 'date'];

    public function inquiry() { return $this->belongsTo(PurchaseInquiry::class, 'inquiry_id'); }
    public function supplier() { return $this->belongsTo(Supplier::class); }
    public function product() { return $this->belongsTo(Product::class); }
}
