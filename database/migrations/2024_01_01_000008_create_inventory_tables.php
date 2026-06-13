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
        // 库存主表
        Schema::create('inventories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id')->comment('产品ID');
            $table->unsignedBigInteger('sku_id')->comment('SKU ID');
            $table->unsignedBigInteger('warehouse_id')->comment('仓库ID');
            $table->unsignedBigInteger('location_id')->nullable()->comment('库位ID');
            $table->string('batch_no', 64)->nullable()->comment('批次号');
            $table->decimal('quantity', 10, 2)->default(0)->comment('库存数量');
            $table->decimal('locked_quantity', 10, 2)->default(0)->comment('锁定数量');
            $table->decimal('cost_price', 12, 2)->default(0)->comment('库存成本价');
            $table->date('manufacturing_date')->nullable()->comment('生产日期');
            $table->date('expiry_date')->nullable()->comment('有效期至');
            $table->timestamp('update_time')->nullable()->comment('更新时间');
            $table->timestamps();
            
            $table->foreign('product_id')->on('products')->references('id')->onDelete('cascade');
            $table->foreign('sku_id')->on('product_skus')->references('id')->onDelete('cascade');
            $table->foreign('warehouse_id')->on('warehouses')->references('id')->onDelete('cascade');
            $table->unique(['sku_id', 'warehouse_id', 'location_id', 'batch_no'], 'sku_warehouse_location_batch');
            $table->index('product_id');
        });

        // 库存流水日志
        Schema::create('inventory_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id')->comment('产品ID');
            $table->unsignedBigInteger('sku_id')->comment('SKU ID');
            $table->unsignedBigInteger('warehouse_id')->comment('仓库ID');
            $table->unsignedBigInteger('location_id')->nullable()->comment('库位ID');
            $table->string('batch_no', 64)->nullable()->comment('批次号');
            $table->tinyInteger('type')->comment('类型代码');
            $table->decimal('quantity_before', 10, 2)->comment('变动前数量');
            $table->decimal('quantity_change', 10, 2)->comment('变动数量');
            $table->decimal('quantity_after', 10, 2)->comment('变动后数量');
            $table->decimal('cost_price', 12, 2)->nullable()->comment('入库成本价');
            $table->decimal('original_cost', 12, 2)->nullable()->comment('原成本价');
            $table->string('reference_type', 32)->nullable()->comment('关联单据类型');
            $table->unsignedBigInteger('reference_id')->nullable()->comment('关联单据ID');
            $table->unsignedBigInteger('return_order_id')->nullable()->comment('关联退货单');
            $table->text('notes')->nullable()->comment('备注');
            $table->unsignedBigInteger('employee_id')->nullable()->comment('操作人');
            $table->timestamp('create_time')->useCurrent()->comment('操作时间');
            
            $table->index(['sku_id', 'warehouse_id']);
            $table->index('type');
            $table->index('create_time');
        });

        // 库存调拨单
        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('transfer_no', 32)->unique()->comment('调拨单号');
            $table->unsignedBigInteger('from_warehouse_id')->comment('调出仓库');
            $table->unsignedBigInteger('to_warehouse_id')->comment('调入仓库');
            $table->tinyInteger('status')->default(0)->comment('状态');
            $table->text('notes')->nullable()->comment('备注');
            $table->unsignedBigInteger('employee_id')->nullable()->comment('创建人');
            $table->unsignedBigInteger('approver_id')->nullable()->comment('审批人');
            $table->timestamp('approved_at')->nullable()->comment('审批时间');
            $table->timestamps();
            
            $table->foreign('from_warehouse_id')->on('warehouses')->references('id');
            $table->foreign('to_warehouse_id')->on('warehouses')->references('id');
        });

        // 调拨明细
        Schema::create('stock_transfer_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('transfer_id')->comment('调拨单ID');
            $table->unsignedBigInteger('product_id')->comment('产品ID');
            $table->unsignedBigInteger('sku_id')->comment('SKU ID');
            $table->decimal('quantity', 10, 2)->comment('调拨数量');
            $table->decimal('transferred_qty', 10, 2)->default(0)->comment('已调拨数量');
            $table->unsignedBigInteger('from_location_id')->nullable()->comment('调出库位');
            $table->unsignedBigInteger('to_location_id')->nullable()->comment('调入库位');
            $table->timestamps();
            
            $table->foreign('transfer_id')->on('stock_transfers')->references('id')->onDelete('cascade');
            $table->foreign('product_id')->on('products')->references('id');
            $table->foreign('sku_id')->on('product_skus')->references('id');
        });

        // 库存冻结记录
        Schema::create('inventory_freezes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id')->comment('产品ID');
            $table->unsignedBigInteger('sku_id')->comment('SKU ID');
            $table->unsignedBigInteger('warehouse_id')->comment('仓库ID');
            $table->decimal('quantity', 10, 2)->comment('冻结数量');
            $table->tinyInteger('reason')->comment('原因: 1退换货 2质检 3法院冻结 4其他');
            $table->unsignedBigInteger('order_id')->nullable()->comment('关联单据');
            $table->timestamp('unfreeze_time')->nullable()->comment('计划解冻时间');
            $table->tinyInteger('status')->default(1)->comment('状态: 1冻结中 2已解冻');
            $table->unsignedBigInteger('unfreeze_by')->nullable()->comment('解冻人');
            $table->timestamp('unfreeze_at')->nullable()->comment('解冻时间');
            $table->timestamps();
            
            $table->foreign('product_id')->on('products')->references('id');
            $table->foreign('sku_id')->on('product_skus')->references('id');
            $table->foreign('warehouse_id')->on('warehouses')->references('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_freezes');
        Schema::dropIfExists('stock_transfer_items');
        Schema::dropIfExists('stock_transfers');
        Schema::dropIfExists('inventory_logs');
        Schema::dropIfExists('inventories');
    }
};
