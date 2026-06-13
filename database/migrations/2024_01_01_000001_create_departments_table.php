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
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parent_id')->default(0)->comment('父部门ID');
            $table->string('name', 128)->comment('部门名称');
            $table->string('code', 64)->nullable()->comment('部门编码');
            $table->unsignedBigInteger('manager_id')->nullable()->comment('部门负责人');
            $table->tinyInteger('status')->default(1)->comment('状态: 1启用 0停用');
            $table->timestamps();
            
            $table->index('parent_id');
            $table->unique('code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('departments');
    }
};
