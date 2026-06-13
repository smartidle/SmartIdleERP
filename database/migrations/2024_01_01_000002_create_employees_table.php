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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique()->comment('工号');
            $table->string('name', 128)->comment('姓名');
            $table->string('email', 128)->unique()->comment('邮箱(登录账号)');
            $table->string('password')->comment('密码');
            $table->unsignedBigInteger('department_id')->nullable()->comment('部门');
            $table->string('position', 64)->nullable()->comment('职位');
            $table->unsignedBigInteger('role_id')->nullable()->comment('角色ID');
            $table->tinyInteger('status')->default(1)->comment('状态: 1启用 0停用');
            $table->string('preferred_lang', 10)->default('zh_CN')->comment('偏好语言');
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('department_id');
            $table->index('role_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
