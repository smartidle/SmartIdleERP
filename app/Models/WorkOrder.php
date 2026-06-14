<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkOrder extends Model
{

    protected $table = 'work_orders';

    protected $fillable = [
        'wo_no', 'bom_id', 'product_id', 'sku_id', 'warehouse_id',
        'planned_qty', 'completed_qty', 'scrap_qty', 'priority',
        'work_hours', 'actual_work_hours', 'quality_rate', 'status',
        'sales_order_id', 'planned_start', 'planned_end',
        'actual_start', 'actual_end', 'employee_id', 'approver_id', 'remark',
    ];

    protected $casts = [
        'planned_start' => 'date',
        'planned_end' => 'date',
        'actual_start' => 'date',
        'actual_end' => 'date',
    ];

    // 状态常量
    const STATUS_PENDING = 0;     // 待审核
    const STATUS_APPROVED = 1;    // 已审核
    const STATUS_IN_PROGRESS = 2; // 生产中
    const STATUS_COMPLETED = 3;   // 已完工
    const STATUS_CLOSED = 4;      // 已结案
    const STATUS_CANCELLED = 5;   // 已取消

    // 优先级
    const PRIORITY_NORMAL = 1;
    const PRIORITY_URGENT = 2;
    const PRIORITY_CRITICAL = 3;

    public function bom()
    {
        return $this->belongsTo(Bom::class, 'bom_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function sku()
    {
        return $this->belongsTo(ProductSku::class, 'sku_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function operations()
    {
        return $this->hasMany(WorkOrderOperation::class, 'work_order_id');
    }

    public function reports()
    {
        return $this->hasMany(WorkOrderReport::class, 'work_order_id');
    }

    public function materials()
    {
        return $this->hasMany(WorkOrderMaterial::class, 'work_order_id');
    }
}
