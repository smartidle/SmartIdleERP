<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_checks', function (Blueprint $table) {
            $table->id();
            $table->string('check_no', 32)->unique()->comment('盘点单号');
            $table->unsignedBigInteger('warehouse_id')->comment('仓库');
            $table->date('check_date')->comment('盘点日期');
            $table->tinyInteger('type')->default(1)->comment('1=全盘 2=抽盘');
            $table->tinyInteger('status')->default(1)->comment('1=盘点中 2=待审核 3=已审核 4=已拒绝');
            $table->text('remark')->nullable()->comment('备注');
            $table->unsignedBigInteger('employee_id')->comment('盘点人');
            $table->unsignedBigInteger('approver_id')->nullable()->comment('审核人');
            $table->timestamp('approved_at')->nullable()->comment('审核时间');
            $table->timestamps();

            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('set null');
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('set null');
            $table->index('check_no');
            $table->index('status');
        });

        Schema::create('inventory_check_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('check_id')->comment('盘点单ID');
            $table->unsignedBigInteger('product_id')->comment('产品');
            $table->unsignedBigInteger('sku_id')->comment('SKU');
            $table->decimal('system_qty', 14, 3)->comment('系统库存');
            $table->decimal('actual_qty', 14, 3)->comment('实际库存');
            $table->decimal('difference', 14, 3)->comment('差异数量');
            $table->decimal('unit_cost', 14, 2)->nullable()->comment('成本单价');
            $table->decimal('difference_amount', 14, 2)->nullable()->comment('差异金额');
            $table->tinyInteger('status')->default(1)->comment('1=待确认 2=已调整 3=盘盈 4=盘亏');
            $table->text('reason')->nullable()->comment('差异原因');
            $table->text('remark')->nullable()->comment('备注');
            $table->timestamps();

            $table->foreign('check_id')->references('id')->on('inventory_checks')->onDelete('cascade');
            $table->index('check_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_check_items');
        Schema::dropIfExists('inventory_checks');
    }
};