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
        // 销售发货单
        Schema::create('sales_deliveries', function (Blueprint $table) {
            $table->id();
            $table->string('delivery_no', 32)->unique()->comment('发货单号');
            $table->unsignedBigInteger('order_id')->comment('关联订单');
            $table->unsignedBigInteger('warehouse_id')->comment('发货仓库');
            $table->date('delivery_date')->comment('发货日期');
            $table->tinyInteger('status')->default(1)->comment('状态: 1待发货 2已发货 3已确认收货');
            $table->tinyInteger('is_split')->default(0)->comment('是否拆单: 1是 0否');
            $table->unsignedBigInteger('parent_id')->nullable()->comment('父发货单ID');
            $table->string('package_no', 64)->nullable()->comment('包裹号');
            $table->string('express_company', 64)->nullable()->comment('快递公司');
            $table->string('tracking_no', 128)->nullable()->comment('物流单号');
            $table->decimal('weight', 10, 3)->nullable()->comment('包裹重量(kg)');
            $table->decimal('shipping_fee', 12, 2)->nullable()->comment('运费');
            $table->string('sender_name', 64)->nullable()->comment('发件人');
            $table->string('sender_phone', 32)->nullable()->comment('发件人电话');
            $table->text('sender_address')->nullable()->comment('发件人地址');
            $table->string('receiver_name', 64)->nullable()->comment('收件人');
            $table->string('receiver_phone', 32)->nullable()->comment('收件人电话');
            $table->text('receiver_address')->nullable()->comment('收件人地址');
            $table->unsignedBigInteger('shipped_by')->nullable()->comment('发货人');
            $table->timestamp('shipped_at')->nullable()->comment('发货时间');
            $table->text('notes')->nullable()->comment('备注');
            $table->timestamps();
            
            $table->foreign('order_id')->on('sales_orders')->references('id');
            $table->foreign('warehouse_id')->on('warehouses')->references('id');
            $table->index('status');
        });

        // 发货明细
        Schema::create('sales_delivery_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('delivery_id')->comment('发货单ID');
            $table->unsignedBigInteger('order_item_id')->comment('订单明细ID');
            $table->unsignedBigInteger('product_id')->comment('产品ID');
            $table->unsignedBigInteger('sku_id')->nullable()->comment('SKU ID');
            $table->decimal('quantity', 10, 2)->comment('发货数量');
            $table->timestamps();
            
            $table->foreign('delivery_id')->on('sales_deliveries')->references('id')->onDelete('cascade');
        });

        // 销售退货单
        Schema::create('sales_returns', function (Blueprint $table) {
            $table->id();
            $table->string('return_no', 32)->unique()->comment('退货单号');
            $table->unsignedBigInteger('order_id')->comment('原订单ID');
            $table->unsignedBigInteger('customer_id')->comment('客户ID');
            $table->tinyInteger('return_type')->comment('退货类型: 1退货退款 2仅退款 3换货');
            $table->string('reason', 64)->comment('退货原因');
            $table->text('reason_detail')->nullable()->comment('原因详情');
            $table->text('images')->nullable()->comment('凭证图片(JSON数组)');
            $table->decimal('quantity', 10, 2)->nullable()->comment('退货总数量');
            $table->decimal('amount', 12, 2)->default(0)->comment('退款金额');
            $table->tinyInteger('status')->default(0)->comment('状态: 0申请中 1已审批 2已收货 3已退款 4已拒绝 5已取消 6换货发出');
            $table->unsignedBigInteger('warehouse_id')->nullable()->comment('收货仓库');
            $table->unsignedBigInteger('employee_id')->nullable()->comment('处理人');
            $table->unsignedBigInteger('approved_by')->nullable()->comment('审批人');
            $table->timestamp('approved_at')->nullable()->comment('审批时间');
            $table->timestamp('received_at')->nullable()->comment('收货时间');
            $table->timestamp('refunded_at')->nullable()->comment('退款时间');
            $table->tinyInteger('refund_method')->nullable()->comment('退款方式: 1原路退回 2退到余额 3退到账户');
            $table->text('notes')->nullable()->comment('备注');
            $table->timestamps();
            
            $table->foreign('order_id')->on('sales_orders')->references('id');
            $table->foreign('customer_id')->on('customers')->references('id');
            $table->index('status');
        });

        // 退货明细
        Schema::create('sales_return_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('return_id')->comment('退货单ID');
            $table->unsignedBigInteger('order_item_id')->comment('原订单明细ID');
            $table->unsignedBigInteger('product_id')->comment('产品ID');
            $table->unsignedBigInteger('sku_id')->nullable()->comment('SKU ID');
            $table->decimal('order_qty', 10, 2)->nullable()->comment('原订单数量');
            $table->decimal('return_qty', 10, 2)->comment('退货数量');
            $table->decimal('return_price', 12, 2)->nullable()->comment('退货单价');
            $table->decimal('return_amount', 12, 2)->comment('退款金额');
            $table->tinyInteger('is_replacement')->default(1)->comment('是否换货: 1否 2换货发出');
            $table->timestamps();
            
            $table->foreign('return_id')->on('sales_returns')->references('id')->onDelete('cascade');
        });

        // 退款记录表
        Schema::create('refund_records', function (Blueprint $table) {
            $table->id();
            $table->string('refund_no', 32)->unique()->comment('退款单号');
            $table->unsignedBigInteger('return_id')->nullable()->comment('退货单ID');
            $table->unsignedBigInteger('order_id')->comment('原订单ID');
            $table->unsignedBigInteger('customer_id')->comment('客户ID');
            $table->decimal('amount', 12, 2)->comment('退款金额');
            $table->tinyInteger('refund_type')->comment('退款类型: 1仅退款 2退货退款 3补偿退款');
            $table->tinyInteger('refund_method')->comment('退款方式: 1原路退回 2退到余额 3退到账户');
            $table->string('transaction_id', 64)->nullable()->comment('第三方交易号');
            $table->string('account_info', 128)->nullable()->comment('账户信息');
            $table->tinyInteger('status')->default(1)->comment('状态: 1处理中 2成功 3失败');
            $table->timestamp('process_time')->nullable()->comment('处理时间');
            $table->text('notes')->nullable()->comment('备注');
            $table->timestamps();
            
            $table->foreign('order_id')->on('sales_orders')->references('id');
            $table->foreign('customer_id')->on('customers')->references('id');
        });

        // 退货入库单
        Schema::create('sales_return_deliveries', function (Blueprint $table) {
            $table->id();
            $table->string('delivery_no', 32)->unique()->comment('入库单号');
            $table->unsignedBigInteger('return_id')->comment('退货单ID');
            $table->unsignedBigInteger('warehouse_id')->comment('入库仓库');
            $table->unsignedBigInteger('received_by')->nullable()->comment('收货人');
            $table->timestamp('received_at')->nullable()->comment('收货时间');
            $table->tinyInteger('quality_status')->default(1)->comment('质检状态: 1合格 2部分合格 3不合格');
            $table->text('notes')->nullable()->comment('备注');
            $table->timestamps();
            
            $table->foreign('return_id')->on('sales_returns')->references('id');
            $table->foreign('warehouse_id')->on('warehouses')->references('id');
        });

        // 退货入库明细
        Schema::create('sales_return_delivery_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('delivery_id')->comment('入库单ID');
            $table->unsignedBigInteger('return_item_id')->nullable()->comment('退货明细ID');
            $table->unsignedBigInteger('product_id')->comment('产品ID');
            $table->unsignedBigInteger('sku_id')->nullable()->comment('SKU ID');
            $table->decimal('return_qty', 10, 2)->nullable()->comment('退货数量');
            $table->decimal('received_qty', 10, 2)->nullable()->comment('收货数量');
            $table->decimal('qualified_qty', 10, 2)->nullable()->comment('合格数量');
            $table->decimal('defective_qty', 10, 2)->nullable()->comment('不合格数量');
            $table->decimal('cost_price', 12, 2)->nullable()->comment('入库成本价');
            $table->timestamps();
            
            $table->foreign('delivery_id')->on('sales_return_deliveries')->references('id')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_return_delivery_items');
        Schema::dropIfExists('sales_return_deliveries');
        Schema::dropIfExists('refund_records');
        Schema::dropIfExists('sales_return_items');
        Schema::dropIfExists('sales_returns');
        Schema::dropIfExists('sales_delivery_items');
        Schema::dropIfExists('sales_deliveries');
    }
};
