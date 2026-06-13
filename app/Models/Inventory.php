<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    use HasFactory;

    protected $table = 'inventories';

    protected $fillable = [
        'product_id',
        'sku_id',
        'warehouse_id',
        'location_id',
        'batch_no',
        'quantity',
        'locked_quantity',
        'cost_price',
        'manufacturing_date',
        'expiry_date',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'locked_quantity' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'manufacturing_date' => 'date',
        'expiry_date' => 'date',
    ];

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

    // 关联仓库
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    // 关联库位
    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    // 关联库存日志
    public function logs()
    {
        return $this->hasMany(InventoryLog::class);
    }

    // 获取可用库存
    public function getAvailableQuantityAttribute()
    {
        return max(0, $this->quantity - $this->locked_quantity);
    }

    // 检查是否过期
    public function isExpired()
    {
        if (!$this->expiry_date) {
            return false;
        }
        return $this->expiry_date < now()->toDateString();
    }

    // 检查是否低于安全库存
    public function isLowStock()
    {
        $product = $this->product;
        if (!$product || $product->min_stock <= 0) {
            return false;
        }
        return $this->available_quantity < $product->min_stock;
    }
}
