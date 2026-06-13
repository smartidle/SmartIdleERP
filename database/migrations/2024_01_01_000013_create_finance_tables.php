<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 财务科目表
        Schema::create('finance_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique()->comment('科目编码');
            $table->string('name', 128)->comment('科目名称');
            $table->unsignedBigInteger('parent_id')->nullable()->comment('父科目ID');
            $table->tinyInteger('type')->comment('类型: 1资产 2负债 3所有者权益 4收入 5费用');
            $table->decimal('balance', 14, 2)->default(0)->comment('当前余额');
            $table->tinyInteger('is_system')->default(0)->comment('是否系统内置');
            $table->tinyInteger('status')->default(1)->comment('状态');
            $table->timestamps();
            
            $table->index('parent_id');
            $table->index('type');
        });

        // 收款单
        Schema::create('finance_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_no', 32)->unique()->comment('收款单号');
            $table->tinyInteger('receipt_type')->comment('类型: 1销售收款 2预收款 3退款 9其他');
            $table->unsignedBigInteger('order_id')->nullable()->comment('关联销售订单ID');
            $table->unsignedBigInteger('customer_id')->comment('客户ID');
            $table->decimal('amount', 12, 2)->comment('收款金额');
            $table->unsignedBigInteger('advance_receipt_id')->nullable()->comment('使用预收款ID');
            $table->tinyInteger('payment_method')->default(2)->comment('付款方式');
            $table->date('payment_date')->comment('收款日期');
            $table->string('invoice_no', 64)->nullable()->comment('发票号');
            $table->unsignedBigInteger('account_id')->nullable()->comment('收款账户');
            $table->tinyInteger('reconcile_status')->default(0)->comment('核销状态: 0未核销 1部分核销 2已核销');
            $table->tinyInteger('status')->default(1)->comment('状态');
            $table->text('notes')->nullable()->comment('备注');
            $table->unsignedBigInteger('employee_id')->nullable()->comment('经办人');
            $table->timestamps();
            
            $table->foreign('customer_id')->on('customers')->references('id');
            $table->index('receipt_type');
            $table->index('payment_date');
        });

        // 付款单
        Schema::create('finance_payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_no', 32)->unique()->comment('付款单号');
            $table->tinyInteger('payment_type')->comment('类型: 1采购付款 2预付款 3退款 9其他');
            $table->unsignedBigInteger('order_id')->nullable()->comment('关联采购订单ID');
            $table->unsignedBigInteger('supplier_id')->comment('供应商ID');
            $table->decimal('amount', 12, 2)->comment('付款金额');
            $table->tinyInteger('payment_method')->default(2)->comment('付款方式');
            $table->date('payment_date')->comment('付款日期');
            $table->string('invoice_no', 64)->nullable()->comment('发票号');
            $table->unsignedBigInteger('account_id')->nullable()->comment('付款账户');
            $table->tinyInteger('status')->default(1)->comment('状态');
            $table->text('notes')->nullable()->comment('备注');
            $table->unsignedBigInteger('employee_id')->nullable()->comment('经办人');
            $table->timestamps();
            
            $table->foreign('supplier_id')->on('suppliers')->references('id');
            $table->index('payment_type');
        });

        // 发票表
        Schema::create('finance_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_no', 64)->unique()->comment('发票号');
            $table->tinyInteger('type')->comment('类型: 1销售发票 2采购发票');
            $table->unsignedBigInteger('customer_id')->nullable()->comment('客户');
            $table->unsignedBigInteger('supplier_id')->nullable()->comment('供应商');
            $table->decimal('amount', 12, 2)->comment('发票金额');
            $table->decimal('tax_amount', 12, 2)->nullable()->comment('税额');
            $table->date('invoice_date')->comment('开票日期');
            $table->tinyInteger('status')->default(1)->comment('状态');
            $table->text('notes')->nullable()->comment('备注');
            $table->timestamps();
            
            $table->index('type');
        });

        // 发票匹配表
        Schema::create('invoice_matches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('invoice_id')->comment('发票ID');
            $table->unsignedBigInteger('order_id')->nullable()->comment('关联订单ID');
            $table->unsignedBigInteger('receipt_id')->nullable()->comment('关联收款单ID');
            $table->unsignedBigInteger('payment_id')->nullable()->comment('关联付款单ID');
            $table->decimal('amount', 12, 2)->comment('匹配金额');
            $table->timestamps();
            
            $table->foreign('invoice_id')->on('finance_invoices')->references('id')->onDelete('cascade');
        });

        // 核销记录表
        Schema::create('reconciles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('receipt_id')->nullable()->comment('收款单ID');
            $table->unsignedBigInteger('order_id')->comment('订单ID');
            $table->decimal('amount', 12, 2)->comment('核销金额');
            $table->timestamp('create_time')->useCurrent()->comment('时间');
            
            $table->foreign('order_id')->on('sales_orders')->references('id');
        });

        // 成本记录表
        Schema::create('cost_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id')->comment('产品ID');
            $table->unsignedBigInteger('sku_id')->nullable()->comment('SKU ID');
            $table->tinyInteger('cost_type')->comment('成本类型: 1采购成本 2生产成本 3其他');
            $table->decimal('amount', 12, 2)->comment('金额');
            $table->decimal('quantity', 10, 2)->nullable()->comment('数量');
            $table->decimal('unit_cost', 12, 2)->nullable()->comment('单位成本');
            $table->string('reference_type', 32)->nullable()->comment('来源单据类型');
            $table->unsignedBigInteger('reference_id')->nullable()->comment('来源单据ID');
            $table->timestamp('create_time')->useCurrent()->comment('时间');
            
            $table->foreign('product_id')->on('products')->references('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cost_records');
        Schema::dropIfExists('reconciles');
        Schema::dropIfExists('invoice_matches');
        Schema::dropIfExists('finance_invoices');
        Schema::dropIfExists('finance_payments');
        Schema::dropIfExists('finance_receipts');
        Schema::dropIfExists('finance_accounts');
    }
};
