<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerAddress extends Model
{
    use HasFactory;

    protected $table = 'customer_addresses';

    protected $fillable = [
        'customer_id',
        'address',
        'contact_person',
        'phone',
        'is_default',
    ];

    // 关联客户
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
