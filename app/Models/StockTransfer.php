<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockTransfer extends Model
{
    protected $table = 'stock_transfers';

    protected $fillable = [
        'transfer_no', 'from_warehouse_id', 'to_warehouse_id',
        'status', 'notes', 'employee_id', 'approver_id', 'approved_at',
    ];

    protected $casts = ['approved_at' => 'datetime'];

    const STATUS_DRAFT = 0;
    const STATUS_APPROVED = 1;
    const STATUS_IN_TRANSIT = 2;
    const STATUS_COMPLETED = 3;
    const STATUS_CANCELLED = 4;

    public function items()
    {
        return $this->hasMany(StockTransferItem::class, 'transfer_id');
    }

    public function fromWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    public function toWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }
}
