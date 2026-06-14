<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseInquiryItem extends Model
{
    protected $table = 'purchase_inquiry_items';

    protected $fillable = ['inquiry_id', 'product_id', 'sku_id', 'quantity'];

    public function inquiry() { return $this->belongsTo(PurchaseInquiry::class, 'inquiry_id'); }
    public function product() { return $this->belongsTo(Product::class); }
    public function sku() { return $this->belongsTo(ProductSku::class, 'sku_id'); }
}
