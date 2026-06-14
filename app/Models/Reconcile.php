<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reconcile extends Model
{
    public $timestamps = false;
    protected $table = 'reconciles';

    protected $fillable = ['receipt_id', 'order_id', 'amount', 'create_time'];

    protected $casts = ['create_time' => 'datetime'];

    public function order() { return $this->belongsTo(SalesOrder::class, 'order_id'); }
}
