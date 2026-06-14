<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrintTemplate extends Model
{
    protected $table = 'print_templates';

    protected $fillable = ['name', 'type', 'content', 'is_default'];

    // 模板类型: 1=销售订单 2=采购订单 3=发货单 4=收货单 5=发票 6=报价单 7=对账单
}
