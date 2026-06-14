<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkOrderMaterial extends Model
{
    protected $table = 'work_order_materials';

    protected $fillable = [
        'work_order_id', 'product_id', 'sku_id',
        'required_qty', 'issued_qty', 'returned_qty', 'warehouse_id',
    ];

    public function workOrder()
    {
        return $this->belongsTo(WorkOrder::class, 'work_order_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function sku()
    {
        return $this->belongsTo(ProductSku::class, 'sku_id');
    }
}
