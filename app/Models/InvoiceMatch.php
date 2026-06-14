<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceMatch extends Model
{
    protected $table = 'invoice_matches';

    protected $fillable = [
        'invoice_id', 'order_id', 'receipt_id', 'payment_id', 'amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function invoice()
    {
        return $this->belongsTo(FinanceInvoice::class, 'invoice_id');
    }

    public function order()
    {
        return $this->belongsTo(SalesOrder::class, 'order_id');
    }

    public function receipt()
    {
        return $this->belongsTo(FinanceReceipt::class, 'receipt_id');
    }

    public function payment()
    {
        return $this->belongsTo(FinancePayment::class, 'payment_id');
    }
}