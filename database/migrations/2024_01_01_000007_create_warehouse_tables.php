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
        // 仓库表
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique()->comment('仓库编码');
            $table->string('name', 128)->comment('仓库名称');
            $table->tinyInteger('type')->default(1)->comment('类型: 1正品仓 2次品仓 3原材料仓 4半成品仓 5成品仓');
            $table->text('address')->nullable()->comment('仓库地址');
            $table->unsignedBigInteger('manager_id')->nullable()->comment('仓管负责人');
            $table->tinyInteger('is_default')->default(0)->comment('是否默认仓库');
            $table->decimal('capacity', 12, 2)->nullable()->comment('仓库容量');
            $table->tinyInteger('status')->default(1)->comment('状态');
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('type');
            $table->index('status');
        });

        // 库位表
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('warehouse_id')->comment('所属仓库');
            $table->string('code', 32)->unique()->comment('库位编码');
            $table->string('zone', 32)->nullable()->comment('区域');
            $table->string('shelf', 16)->nullable()->comment('货架号');
            $table->tinyInteger('layer')->nullable()->comment('层号');
            $table->string('position', 32)->nullable()->comment('仓位');
            $table->decimal('capacity', 12, 2)->nullable()->comment('库位容量');
            $table->tinyInteger('status')->default(1)->comment('状态');
            $table->timestamps();
            
            $table->foreign('warehouse_id')->on('warehouses')->references('id')->onDelete('cascade');
            $table->index('warehouse_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('locations');
        Schema::dropIfExists('warehouses');
    }
};
