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
        // BOM物料清单表
        Schema::create('boms', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique()->comment('BOM编号');
            $table->unsignedBigInteger('product_id')->comment('成品ID');
            $table->unsignedBigInteger('sku_id')->nullable()->comment('成品SKU');
            $table->string('version', 16)->default('V1.0')->comment('版本号');
            $table->decimal('quantity', 10, 2)->default(1)->comment('成品产出数量');
            $table->decimal('unit_cost', 12, 2)->default(0)->comment('标准单位成本');
            $table->tinyInteger('status')->default(0)->comment('状态: 0草稿 1生效 2失效');
            $table->date('effective_date')->nullable()->comment('生效日期');
            $table->date('invalid_date')->nullable()->comment('失效日期');
            $table->text('remark')->nullable()->comment('备注');
            $table->timestamps();
            
            $table->foreign('product_id')->on('products')->references('id');
            $table->index('status');
        });

        // BOM明细表
        Schema::create('bom_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bom_id')->comment('BOM ID');
            $table->unsignedBigInteger('product_id')->comment('原材料产品ID');
            $table->unsignedBigInteger('sku_id')->nullable()->comment('原材料SKU');
            $table->decimal('quantity', 10, 2)->comment('所需数量');
            $table->string('unit', 32)->nullable()->comment('单位');
            $table->decimal('loss_rate', 5, 2)->default(0)->comment('损耗率(%)');
            $table->decimal('actual_quantity', 10, 2)->nullable()->comment('实际用量');
            $table->text('remark')->nullable()->comment('备注');
            $table->timestamps();
            
            $table->foreign('bom_id')->on('boms')->references('id')->onDelete('cascade');
            $table->foreign('product_id')->on('products')->references('id');
        });

        // BOM变更历史
        Schema::create('bom_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bom_id')->comment('BOM ID');
            $table->string('version', 16)->comment('版本号');
            $table->string('change_type', 32)->comment('变更类型');
            $table->text('old_data')->nullable()->comment('变更前数据');
            $table->text('new_data')->nullable()->comment('变更后数据');
            $table->unsignedBigInteger('employee_id')->nullable()->comment('变更人');
            $table->timestamp('create_time')->useCurrent()->comment('变更时间');
            
            $table->foreign('bom_id')->on('boms')->references('id')->onDelete('cascade');
        });

        // 生产工单表
        Schema::create('work_orders', function (Blueprint $table) {
            $table->id();
            $table->string('wo_no', 32)->unique()->comment('工单编号');
            $table->unsignedBigInteger('bom_id')->comment('BOM ID');
            $table->unsignedBigInteger('product_id')->comment('生产产品ID');
            $table->unsignedBigInteger('sku_id')->nullable()->comment('生产SKU');
            $table->unsignedBigInteger('warehouse_id')->nullable()->comment('领料/入库仓库');
            $table->decimal('planned_qty', 10, 2)->comment('计划生产数量');
            $table->decimal('completed_qty', 10, 2)->default(0)->comment('已完工数量');
            $table->decimal('scrap_qty', 10, 2)->default(0)->comment('报废数量');
            $table->tinyInteger('priority')->default(1)->comment('优先级: 1普通 2紧急 3非常紧急');
            $table->decimal('work_hours', 8, 2)->nullable()->comment('计划工时');
            $table->decimal('actual_work_hours', 8, 2)->nullable()->comment('实际工时');
            $table->decimal('quality_rate', 5, 2)->nullable()->comment('合格率(%)');
            $table->tinyInteger('status')->default(0)->comment('状态');
            $table->unsignedBigInteger('sales_order_id')->nullable()->comment('关联销售订单');
            $table->date('planned_start')->nullable()->comment('计划开始日期');
            $table->date('planned_end')->nullable()->comment('计划完成日期');
            $table->date('actual_start')->nullable()->comment('实际开始日期');
            $table->date('actual_end')->nullable()->comment('实际完成日期');
            $table->unsignedBigInteger('employee_id')->nullable()->comment('创建人');
            $table->unsignedBigInteger('approver_id')->nullable()->comment('审批人');
            $table->text('remark')->nullable()->comment('备注');
            $table->timestamps();
            
            $table->foreign('bom_id')->on('boms')->references('id');
            $table->foreign('product_id')->on('products')->references('id');
            $table->index('status');
        });

        // 工单工序表
        Schema::create('work_order_operations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('work_order_id')->comment('工单ID');
            $table->integer('operation_seq')->comment('工序序号');
            $table->string('operation_name', 128)->comment('工序名称');
            $table->string('work_center', 64)->nullable()->comment('工作中心');
            $table->decimal('standard_hours', 8, 2)->nullable()->comment('标准工时(小时)');
            $table->decimal('actual_hours', 8, 2)->nullable()->comment('实际工时');
            $table->tinyInteger('status')->default(0)->comment('状态: 0待生产 1生产中 2已完成');
            $table->timestamp('start_time')->nullable()->comment('开始时间');
            $table->timestamp('end_time')->nullable()->comment('结束时间');
            $table->unsignedBigInteger('worker_id')->nullable()->comment('作业人员');
            $table->timestamps();
            
            $table->foreign('work_order_id')->on('work_orders')->references('id')->onDelete('cascade');
        });

        // 生产报工记录
        Schema::create('work_order_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('work_order_id')->comment('工单ID');
            $table->decimal('report_qty', 10, 2)->comment('报工数量');
            $table->decimal('qualified_qty', 10, 2)->comment('合格数量');
            $table->decimal('defective_qty', 10, 2)->default(0)->comment('不合格数量');
            $table->date('report_date')->comment('报工日期');
            $table->unsignedBigInteger('reporter_id')->nullable()->comment('报工人');
            $table->text('notes')->nullable()->comment('备注');
            $table->timestamps();
            
            $table->foreign('work_order_id')->on('work_orders')->references('id')->onDelete('cascade');
        });

        // 生产报废单
        Schema::create('work_order_scraps', function (Blueprint $table) {
            $table->id();
            $table->string('scrap_no', 32)->unique()->comment('报废单号');
            $table->unsignedBigInteger('work_order_id')->comment('工单ID');
            $table->unsignedBigInteger('product_id')->comment('产品ID');
            $table->unsignedBigInteger('sku_id')->nullable()->comment('SKU ID');
            $table->decimal('quantity', 10, 2)->comment('报废数量');
            $table->decimal('cost_loss', 12, 2)->nullable()->comment('成本损失');
            $table->string('scrap_reason', 64)->nullable()->comment('报废原因');
            $table->unsignedBigInteger('employee_id')->nullable()->comment('操作人');
            $table->timestamps();
            
            $table->foreign('work_order_id')->on('work_orders')->references('id');
            $table->foreign('product_id')->on('products')->references('id');
        });

        // 工单领料明细
        Schema::create('work_order_materials', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('work_order_id')->comment('工单ID');
            $table->unsignedBigInteger('product_id')->comment('原材料产品ID');
            $table->unsignedBigInteger('sku_id')->nullable()->comment('原材料SKU');
            $table->decimal('required_qty', 10, 2)->comment('需求数量(含损耗)');
            $table->decimal('issued_qty', 10, 2)->default(0)->comment('已领料数量');
            $table->decimal('returned_qty', 10, 2)->default(0)->comment('退料数量');
            $table->unsignedBigInteger('warehouse_id')->nullable()->comment('领料仓库');
            $table->timestamps();
            
            $table->foreign('work_order_id')->on('work_orders')->references('id')->onDelete('cascade');
            $table->foreign('product_id')->on('products')->references('id');
        });

        // 物料齐套检查
        Schema::create('material_kittings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('work_order_id')->comment('工单ID');
            $table->unsignedBigInteger('product_id')->comment('产品ID');
            $table->unsignedBigInteger('sku_id')->nullable()->comment('SKU ID');
            $table->decimal('required_qty', 10, 2)->comment('需求数量');
            $table->decimal('available_qty', 10, 2)->nullable()->comment('可用数量');
            $table->decimal('shortage_qty', 10, 2)->nullable()->comment('缺口数量');
            $table->tinyInteger('status')->default(1)->comment('状态');
            $table->timestamps();
            
            $table->foreign('work_order_id')->on('work_orders')->references('id')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('material_kittings');
        Schema::dropIfExists('work_order_materials');
        Schema::dropIfExists('work_order_scraps');
        Schema::dropIfExists('work_order_reports');
        Schema::dropIfExists('work_order_operations');
        Schema::dropIfExists('work_orders');
        Schema::dropIfExists('bom_histories');
        Schema::dropIfExists('bom_items');
        Schema::dropIfExists('boms');
    }
};
