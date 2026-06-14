<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinanceAccount extends Model
{
    protected $table = 'finance_accounts';

    protected $fillable = [
        'code', 'name', 'parent_id', 'type',
        'balance', 'is_system', 'status',
    ];

    protected $casts = [
        'type' => 'integer',
        'balance' => 'decimal:2',
        'is_system' => 'integer',
        'status' => 'integer',
    ];

    public function parent()
    {
        return $this->belongsTo(FinanceAccount::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(FinanceAccount::class, 'parent_id');
    }
}
