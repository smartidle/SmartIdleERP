<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinanceInvoice extends Model
{
    protected $table = 'finance_invoices';

    protected $fillable = [
        'invoice_no', 'type', 'customer_id', 'supplier_id',
        'amount', 'tax_amount', 'invoice_date', 'status', 'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'status' => 'integer',
    ];

    // Type: 1=销售发票 2=采购发票
    // Status: 1=待开 2=已开 3=已作废
    const TYPE_SALES = 1;
    const TYPE_PURCHASE = 2;
    const STATUS_DRAFT = 1;
    const STATUS_ISSUED = 2;
    const STATUS_VOID = 3;

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function matches()
    {
        return $this->hasMany(InvoiceMatch::class, 'invoice_id');
    }

    public function order()
    {
        return $this->belongsTo(SalesOrder::class, 'order_id');
    }

    /**
     * 开具发票后核销应收款
     */
    public function reconcileWithReceipt($receiptId, $amount)
    {
        \App\Models\InvoiceMatch::create([
            'invoice_id' => $this->id,
            'receipt_id' => $receiptId,
            'amount' => $amount,
        ]);

        $this->update(['status' => self::STATUS_ISSUED]);
    }

    /**
     * 作废发票
     */
    public function void($reason)
    {
        $this->update([
            'status' => self::STATUS_VOID,
            'notes' => ($this->notes ? $this->notes . "\n" : '') . "作废原因: {$reason}",
        ]);
    }
}