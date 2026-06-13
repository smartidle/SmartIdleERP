<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryLog extends Model
{
    use HasFactory;

    protected $table = 'inventory_logs';

    const CREATED_AT = 'create_time';
    const UPDATED_AT = null;

    protected $fillable = [
        'product_id',
        'sku_id',
        'warehouse_id',
        'location_id',
        'batch_no',
        'type',
        'quantity_before',
        'quantity_change',
        'quantity_after',
        'cost_price',
        'original_cost',
        'reference_type',
        'reference_id',
        'return_order_id',
        'notes',
        'employee_id',
    ];

    protected $casts = [
        'quantity_before' => 'decimal:2',
        'quantity_change' => 'decimal:2',
        'quantity_after' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'original_cost' => 'decimal:2',
    ];

    // 库存类型常量
    const TYPE_PURCHASE_IN = 1;      // 采购入库
    const TYPE_SALES_OUT = 2;        // 销售出库
    const TYPE_TRANSFER_IN = 3;      // 调拨入
    const TYPE_TRANSFER_OUT = 4;    // 调拨出
    const TYPE_CHECK_PROFIT = 5;    // 盘点盈
    const TYPE_CHECK_LOSS = 6;      // 盘点亏
    const TYPE_MATERIAL_OUT = 7;     // 生产领料
    const TYPE_PRODUCT_IN = 8;      // 生产入库
    const TYPE_RETURN_IN = 9;        // 退货入库
    const TYPE_RETURN_OUT = 10;      // 退货出库
    const TYPE_FREEZE = 11;          // 冻结
    const TYPE_UNFREEZE = 12;        // 解冻
    const TYPE_OTHER = 99;           // 其他

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

    // 关联操作人
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    // 获取类型名称
    public function getTypeNameAttribute()
    {
        $types = [
            self::TYPE_PURCHASE_IN => '采购入库',
            self::TYPE_SALES_OUT => '销售出库',
            self::TYPE_TRANSFER_IN => '调拨入',
            self::TYPE_TRANSFER_OUT => '调拨出',
            self::TYPE_CHECK_PROFIT => '盘点盈',
            self::TYPE_CHECK_LOSS => '盘点亏',
            self::TYPE_MATERIAL_OUT => '生产领料',
            self::TYPE_PRODUCT_IN => '生产入库',
            self::TYPE_RETURN_IN => '退货入库',
            self::TYPE_RETURN_OUT => '退货出库',
            self::TYPE_FREEZE => '冻结',
            self::TYPE_UNFREEZE => '解冻',
            self::TYPE_OTHER => '其他',
        ];
        return $types[$this->type] ?? '未知';
    }
}
