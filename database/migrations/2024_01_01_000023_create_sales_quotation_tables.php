<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_quotations', function (Blueprint $table) {
            $table->id();
            $table->string('quotation_no', 32)->unique()->comment('报价单号');
            $table->unsignedBigInteger('customer_id')->comment('客户');
            $table->unsignedBigInteger('contact_id')->nullable()->comment('联系人');
            $table->decimal('total_amount', 14, 2)->default(0)->comment('总金额');
            $table->decimal('discount_rate', 5, 2)->default(0)->comment('折扣率%');
            $table->decimal('discount_amount', 14, 2)->default(0)->comment('折扣金额');
            $table->decimal('tax_rate', 5, 2)->default(0)->comment('税率%');
            $table->decimal('tax_amount', 14, 2)->default(0)->comment('税额');
            $table->decimal('final_amount', 14, 2)->default(0)->comment('最终金额');
            $table->date('quotation_date')->comment('报价日期');
            $table->date('valid_until')->nullable()->comment('有效期至');
            $table->tinyInteger('status')->default(1)->comment('1=草稿 2=已发送 3=已接受 4=已拒绝 5=已转订单 6=已过期');
            $table->text('notes')->nullable()->comment('备注');
            $table->text('terms')->nullable()->comment('条款');
            $table->unsignedBigInteger('employee_id')->comment('业务员');
            $table->unsignedBigInteger('converted_order_id')->nullable()->comment('转成的订单ID');
            $table->timestamp('sent_at')->nullable()->comment('发送时间');
            $table->timestamp('accepted_at')->nullable()->comment('接受时间');
            $table->timestamps();

            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('set null');
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('set null');
            $table->index('quotation_no');
            $table->index('status');
        });

        Schema::create('sales_quotation_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('quotation_id')->comment('报价单ID');
            $table->unsignedBigInteger('product_id')->comment('产品');
            $table->unsignedBigInteger('sku_id')->nullable()->comment('SKU');
            $table->string('product_name', 255)->comment('产品名称');
            $table->string('sku_code', 64)->nullable()->comment('SKU编码');
            $table->string('specs', 255)->nullable()->comment('规格');
            $table->decimal('quantity', 14, 3)->comment('数量');
            $table->string('unit', 16)->default('个')->comment('单位');
            $table->decimal('unit_price', 14, 2)->comment('单价');
            $table->decimal('cost_price', 14, 2)->default(0)->comment('成本价');
            $table->decimal('subtotal', 14, 2)->comment('小计');
            $table->decimal('discount_rate', 5, 2)->default(0)->comment('行折扣%');
            $table->decimal('discount_amount', 14, 2)->default(0)->comment('行折扣金额');
            $table->text('remark')->nullable()->comment('备注');
            $table->timestamps();

            $table->foreign('quotation_id')->references('id')->on('sales_quotations')->onDelete('cascade');
            $table->index('quotation_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_quotation_items');
        Schema::dropIfExists('sales_quotations');
    }
};