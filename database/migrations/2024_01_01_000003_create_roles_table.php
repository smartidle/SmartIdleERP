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
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 64)->unique()->comment('角色名称');
            $table->string('code', 32)->unique()->comment('角色编码');
            $table->string('description', 255)->nullable()->comment('描述');
            $table->tinyInteger('status')->default(1)->comment('状态');
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parent_id')->default(0)->comment('父权限ID');
            $table->string('name', 128)->comment('权限名称');
            $table->string('code', 128)->unique()->comment('权限编码');
            $table->tinyInteger('type')->default(1)->comment('类型: 1菜单 2操作 3API');
            $table->string('route', 255)->nullable()->comment('前端路由');
            $table->tinyInteger('status')->default(1)->comment('状态');
            $table->timestamps();
            
            $table->index('parent_id');
        });

        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('role_id')->comment('角色ID');
            $table->unsignedBigInteger('permission_id')->comment('权限ID');
            $table->timestamps();
            
            $table->unique(['role_id', 'permission_id']);
            $table->foreign('role_id')->on('roles')->references('id')->onDelete('cascade');
            $table->foreign('permission_id')->on('permissions')->references('id')->onDelete('cascade');
        });

        Schema::create('data_permissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('role_id')->comment('角色ID');
            $table->string('module', 32)->comment('适用模块');
            $table->tinyInteger('scope_type')->default(1)->comment('范围类型: 1本人 2本部门 3全部');
            $table->timestamps();
            
            $table->foreign('role_id')->on('roles')->references('id')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_permissions');
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};
