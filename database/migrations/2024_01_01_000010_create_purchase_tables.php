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
        // 采购询价单
        Schema::create('purchase_inquiries', function (Blueprint $table) {
            $table->id();
            $table->string('inquiry_no', 32)->unique()->comment('询价单号');
            $table->unsignedBigInteger('employee_id')->nullable()->comment('询价人');
            $table->date('expected_date')->nullable()->comment('期望交货日期');
            $table->text('notes')->nullable()->comment('备注');
            $table->tinyInteger('status')->default(0)->comment('状态: 0询价中 1报价完成 2已转订单 3已取消');
            $table->timestamps();
            
            $table->index('status');
        });

        // 询价明细
        Schema::create('purchase_inquiry_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('inquiry_id')->comment('询价单ID');
            $table->unsignedBigInteger('product_id')->comment('产品ID');
            $table->unsignedBigInteger('sku_id')->nullable()->comment('SKU ID');
            $table->decimal('quantity', 10, 2)->comment('询价数量');
            $table->timestamps();
            
            $table->foreign('inquiry_id')->on('purchase_inquiries')->references('id')->onDelete('cascade');
            $table->foreign('product_id')->on('products')->references('id');
        });

        // 供应商报价记录
        Schema::create('purchase_inquiry_quotes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('inquiry_id')->comment('询价单ID');
            $table->unsignedBigInteger('inquiry_item_id')->nullable()->comment('询价明细ID');
            $table->unsignedBigInteger('supplier_id')->comment('供应商ID');
            $table->unsignedBigInteger('product_id')->comment('产品ID');
            $table->unsignedBigInteger('sku_id')->nullable()->comment('SKU ID');
            $table->decimal('unit_price', 12, 2)->nullable()->comment('报价');
            $table->integer('delivery_days')->nullable()->comment('交货天数');
            $table->date('valid_until')->nullable()->comment('报价有效期');
            $table->tinyInteger('is_selected')->default(0)->comment('是否选中');
            $table->text('notes')->nullable()->comment('备注');
            $table->timestamps();
            
            $table->foreign('inquiry_id')->on('purchase_inquiries')->references('id')->onDelete('cascade');
            $table->foreign('supplier_id')->on('suppliers')->references('id');
        });

        // 采购订单主表
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_no', 32)->unique()->comment('采购单号');
            $table->unsignedBigInteger('inquiry_id')->nullable()->comment('来源询价单');
            $table->unsignedBigInteger('supplier_id')->comment('供应商ID');
            $table->date('order_date')->comment('订单日期');
            $table->date('expected_date')->nullable()->comment('预计到货日期');
            $table->unsignedBigInteger('warehouse_id')->nullable()->comment('入库仓库');
            $table->tinyInteger('status')->default(0)->comment('状态: 0草稿 1待审批 2已审批 3部分到货 4已到货 5已完成 6已取消');
            $table->decimal('subtotal', 12, 2)->default(0)->comment('小计');
            $table->decimal('tax_amount', 12, 2)->default(0)->comment('税额');
            $table->decimal('total_amount', 12, 2)->default(0)->comment('应付总额');
            $table->decimal('paid_amount', 12, 2)->default(0)->comment('已付款金额');
            $table->tinyInteger('payment_status')->default(0)->comment('付款状态');
            $table->text('notes')->nullable()->comment('备注');
            $table->unsignedBigInteger('employee_id')->nullable()->comment('采购员');
            $table->unsignedBigInteger('approver_id')->nullable()->comment('审批人');
            $table->timestamp('approved_at')->nullable()->comment('审批时间');
            $table->timestamps();
            
            $table->foreign('supplier_id')->on('suppliers')->references('id');
            $table->foreign('warehouse_id')->on('warehouses')->references('id');
            $table->index('status');
        });

        // 采购订单明细
        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id')->comment('订单ID');
            $table->unsignedBigInteger('product_id')->comment('产品ID');
            $table->unsignedBigInteger('sku_id')->nullable()->comment('SKU ID');
            $table->string('product_name', 255)->nullable()->comment('产品名称');
            $table->decimal('quantity', 10, 2)->comment('采购数量');
            $table->decimal('unit_price', 12, 2)->comment('采购单价');
            $table->decimal('tax_rate', 5, 2)->default(0)->comment('税率');
            $table->decimal('subtotal', 12, 2)->comment('小计');
            $table->decimal('received_qty', 10, 2)->default(0)->comment('已收货数量');
            $table->timestamps();
            
            $table->foreign('order_id')->on('purchase_orders')->references('id')->onDelete('cascade');
            $table->foreign('product_id')->on('products')->references('id');
        });

        // 采购收货单
        Schema::create('purchase_receives', function (Blueprint $table) {
            $table->id();
            $table->string('receive_no', 32)->unique()->comment('收货单号');
            $table->unsignedBigInteger('order_id')->comment('采购订单ID');
            $table->unsignedBigInteger('warehouse_id')->comment('入库仓库');
            $table->date('receive_date')->comment('收货日期');
            $table->tinyInteger('status')->default(1)->comment('状态: 1待入库 2已入库 3部分入库');
            $table->text('notes')->nullable()->comment('备注');
            $table->unsignedBigInteger('received_by')->nullable()->comment('收货人');
            $table->timestamps();
            
            $table->foreign('order_id')->on('purchase_orders')->references('id');
            $table->foreign('warehouse_id')->on('warehouses')->references('id');
        });

        // 采购收货明细
        Schema::create('purchase_receive_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('receive_id')->comment('收货单ID');
            $table->unsignedBigInteger('order_item_id')->comment('订单明细ID');
            $table->unsignedBigInteger('product_id')->comment('产品ID');
            $table->unsignedBigInteger('sku_id')->nullable()->comment('SKU ID');
            $table->string('batch_no', 64)->nullable()->comment('批次号');
            $table->decimal('quantity', 10, 2)->comment('收货数量');
            $table->decimal('qualified_qty', 10, 2)->nullable()->comment('合格数量');
            $table->decimal('defective_qty', 10, 2)->default(0)->comment('不合格数量');
            $table->decimal('unit_price', 12, 2)->comment('采购单价');
            $table->timestamps();
            
            $table->foreign('receive_id')->on('purchase_receives')->references('id')->onDelete('cascade');
            $table->foreign('order_item_id')->on('purchase_order_items')->references('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_receive_items');
        Schema::dropIfExists('purchase_receives');
        Schema::dropIfExists('purchase_order_items');
        Schema::dropIfExists('purchase_orders');
        Schema::dropIfExists('purchase_inquiry_quotes');
        Schema::dropIfExists('purchase_inquiry_items');
        Schema::dropIfExists('purchase_inquiries');
    }
};
