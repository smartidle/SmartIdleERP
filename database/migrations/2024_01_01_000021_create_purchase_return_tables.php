<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_returns', function (Blueprint $table) {
            $table->id();
            $table->string('return_no', 32)->unique()->comment('退货单号');
            $table->unsignedBigInteger('receive_id')->comment('关联收货单');
            $table->unsignedBigInteger('order_id')->comment('关联采购订单');
            $table->unsignedBigInteger('supplier_id')->comment('供应商');
            $table->string('reason', 255)->nullable()->comment('退货原因');
            $table->decimal('total_amount', 14, 2)->default(0)->comment('退货总金额');
            $table->tinyInteger('status')->default(1)->comment('1=待审核 2=已审核 3=已退货 4=已拒绝');
            $table->unsignedBigInteger('employee_id')->comment('申请人');
            $table->unsignedBigInteger('approver_id')->nullable()->comment('审核人');
            $table->timestamp('approved_at')->nullable()->comment('审核时间');
            $table->unsignedBigInteger('receiver_id')->nullable()->comment('收货人');
            $table->timestamp('received_at')->nullable()->comment('退货收货时间');
            $table->text('remark')->nullable()->comment('备注');
            $table->timestamps();

            $table->foreign('receive_id')->references('id')->on('purchase_receives')->onDelete('cascade');
            $table->foreign('order_id')->references('id')->on('purchase_orders')->onDelete('cascade');
            $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('set null');
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('set null');
            $table->index('return_no');
            $table->index('status');
        });

        Schema::create('purchase_return_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('return_id')->comment('退货单ID');
            $table->unsignedBigInteger('receive_item_id')->comment('收货明细ID');
            $table->unsignedBigInteger('product_id')->comment('产品');
            $table->unsignedBigInteger('sku_id')->nullable()->comment('SKU');
            $table->decimal('quantity', 14, 3)->comment('退货数量');
            $table->decimal('qualified_qty', 14, 3)->default(0)->comment('合格数量');
            $table->decimal('defective_qty', 14, 3)->default(0)->comment('不合格数量');
            $table->decimal('unit_price', 14, 2)->comment('单价');
            $table->decimal('amount', 14, 2)->comment('金额');
            $table->string('defect_reason', 255)->nullable()->comment('不合格原因');
            $table->text('remark')->nullable()->comment('备注');
            $table->timestamps();

            $table->foreign('return_id')->references('id')->on('purchase_returns')->onDelete('cascade');
            $table->foreign('receive_item_id')->references('id')->on('purchase_receive_items')->onDelete('cascade');
            $table->index('return_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_return_items');
        Schema::dropIfExists('purchase_returns');
    }
};