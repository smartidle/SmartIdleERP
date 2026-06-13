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
        // 销售报价单
        Schema::create('sales_quotes', function (Blueprint $table) {
            $table->id();
            $table->string('quote_no', 32)->unique()->comment('报价单号');
            $table->unsignedBigInteger('customer_id')->comment('客户ID');
            $table->unsignedBigInteger('employee_id')->nullable()->comment('销售员');
            $table->integer('valid_days')->default(30)->comment('有效期(天)');
            $table->decimal('subtotal', 12, 2)->default(0)->comment('小计');
            $table->decimal('discount_amount', 12, 2)->default(0)->comment('折扣金额');
            $table->decimal('total_amount', 12, 2)->default(0)->comment('报价总额');
            $table->text('notes')->nullable()->comment('备注');
            $table->tinyInteger('status')->default(0)->comment('状态: 0草稿 1已发送 2已确认 3已失效 4已转订单');
            $table->unsignedBigInteger('convert_order_id')->nullable()->comment('转换后的订单ID');
            $table->timestamps();
            
            $table->foreign('customer_id')->on('customers')->references('id');
            $table->index('status');
        });

        // 报价明细
        Schema::create('sales_quote_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('quote_id')->comment('报价单ID');
            $table->unsignedBigInteger('product_id')->comment('产品ID');
            $table->unsignedBigInteger('sku_id')->nullable()->comment('SKU ID');
            $table->string('product_name', 255)->nullable()->comment('产品名称');
            $table->string('sku_code', 64)->nullable()->comment('SKU编码');
            $table->text('spec')->nullable()->comment('规格');
            $table->decimal('quantity', 10, 2)->comment('数量');
            $table->string('unit', 32)->nullable()->comment('单位');
            $table->decimal('unit_price', 12, 2)->comment('单价');
            $table->decimal('discount_rate', 5, 2)->default(0)->comment('折扣率(%)');
            $table->decimal('subtotal', 12, 2)->comment('小计');
            $table->timestamps();
            
            $table->foreign('quote_id')->on('sales_quotes')->references('id')->onDelete('cascade');
            $table->foreign('product_id')->on('products')->references('id');
        });

        // 销售订单主表
        Schema::create('sales_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_no', 32)->unique()->comment('订单编号');
            $table->unsignedBigInteger('customer_id')->comment('客户ID');
            $table->unsignedBigInteger('quote_id')->nullable()->comment('来源报价单');
            $table->tinyInteger('source')->default(1)->comment('来源: 1手工 2报价转化 3电商同步');
            $table->date('order_date')->comment('订单日期');
            $table->date('delivery_date')->nullable()->comment('期望交货日期');
            $table->unsignedBigInteger('warehouse_id')->nullable()->comment('发货仓库');
            $table->tinyInteger('status')->default(0)->comment('状态: 0草稿 1待审批 2已审批 3部分发货 4已发货 5已完成 6已取消');
            $table->decimal('subtotal', 12, 2)->default(0)->comment('小计(未税)');
            $table->decimal('tax_amount', 12, 2)->default(0)->comment('税额');
            $table->decimal('discount_amount', 12, 2)->default(0)->comment('整单折扣金额');
            $table->decimal('promotion_amount', 12, 2)->default(0)->comment('促销优惠金额');
            $table->decimal('coupon_amount', 12, 2)->default(0)->comment('优惠券金额');
            $table->decimal('total_amount', 12, 2)->default(0)->comment('应收总额');
            $table->decimal('paid_amount', 12, 2)->default(0)->comment('已付款金额');
            $table->tinyInteger('payment_status')->default(0)->comment('付款状态: 0未付 1部分付 2已付清');
            $table->string('shipping_contact', 128)->nullable()->comment('收货人');
            $table->string('shipping_phone', 32)->nullable()->comment('收货人电话');
            $table->text('shipping_address')->nullable()->comment('收货地址');
            $table->decimal('shipping_fee', 12, 2)->default(0)->comment('运费');
            $table->text('notes')->nullable()->comment('订单备注');
            $table->unsignedBigInteger('employee_id')->nullable()->comment('销售员');
            $table->unsignedBigInteger('approver_id')->nullable()->comment('审批人');
            $table->timestamp('approved_at')->nullable()->comment('审批时间');
            $table->timestamp('shipped_at')->nullable()->comment('发货时间');
            $table->timestamp('completed_at')->nullable()->comment('完成时间');
            $table->timestamps();
            
            $table->foreign('customer_id')->on('customers')->references('id');
            $table->foreign('warehouse_id')->on('warehouses')->references('id');
            $table->index('status');
            $table->index('order_date');
        });

        // 销售订单明细
        Schema::create('sales_order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id')->comment('订单ID');
            $table->unsignedBigInteger('product_id')->comment('产品ID');
            $table->unsignedBigInteger('sku_id')->nullable()->comment('SKU ID');
            $table->string('product_name', 255)->nullable()->comment('产品名称');
            $table->string('sku_code', 64)->nullable()->comment('SKU编码');
            $table->text('spec')->nullable()->comment('规格');
            $table->decimal('quantity', 10, 2)->comment('数量');
            $table->string('unit', 32)->nullable()->comment('单位');
            $table->decimal('unit_price', 12, 2)->comment('单价');
            $table->decimal('cost_price', 12, 2)->nullable()->comment('成本价');
            $table->decimal('tax_rate', 5, 2)->default(0)->comment('税率(%)');
            $table->decimal('discount_rate', 5, 2)->default(0)->comment('折扣率(%)');
            $table->decimal('subtotal', 12, 2)->comment('小计');
            $table->decimal('delivered_qty', 10, 2)->default(0)->comment('已发货数量');
            $table->timestamps();
            
            $table->foreign('order_id')->on('sales_orders')->references('id')->onDelete('cascade');
            $table->foreign('product_id')->on('products')->references('id');
        });

        // 订单变更记录
        Schema::create('sales_order_change_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id')->comment('订单ID');
            $table->string('change_type', 32)->comment('变更类型');
            $table->string('field_name', 64)->nullable()->comment('变更字段');
            $table->text('old_value')->nullable()->comment('变更前值');
            $table->text('new_value')->nullable()->comment('变更后值');
            $table->unsignedBigInteger('employee_id')->nullable()->comment('操作人');
            $table->timestamp('create_time')->useCurrent()->comment('时间');
            
            $table->foreign('order_id')->on('sales_orders')->references('id')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_order_change_logs');
        Schema::dropIfExists('sales_order_items');
        Schema::dropIfExists('sales_orders');
        Schema::dropIfExists('sales_quote_items');
        Schema::dropIfExists('sales_quotes');
    }
};
