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
        // 审批流程定义
        Schema::create('approval_flows', function (Blueprint $table) {
            $table->id();
            $table->string('name', 128)->comment('流程名称');
            $table->string('module', 32)->comment('适用模块');
            $table->text('trigger_condition')->nullable()->comment('触发条件(JSON)');
            $table->tinyInteger('is_active')->default(1)->comment('是否启用');
            $table->timestamps();
            
            $table->index('module');
        });

        // 审批节点定义
        Schema::create('approval_nodes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('flow_id')->comment('流程ID');
            $table->string('name', 128)->comment('节点名称');
            $table->integer('node_order')->comment('节点顺序');
            $table->tinyInteger('node_type')->comment('节点类型');
            $table->unsignedBigInteger('approver_id')->nullable()->comment('指定审批人');
            $table->unsignedBigInteger('role_id')->nullable()->comment('指定角色');
            $table->timestamps();
            
            $table->foreign('flow_id')->on('approval_flows')->references('id')->onDelete('cascade');
        });

        // 审批实例
        Schema::create('approval_instances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('flow_id')->nullable()->comment('流程定义ID');
            $table->string('related_type', 32)->comment('关联业务类型');
            $table->unsignedBigInteger('related_id')->comment('关联业务ID');
            $table->unsignedBigInteger('initiator_id')->nullable()->comment('发起人');
            $table->tinyInteger('status')->default(0)->comment('状态');
            $table->timestamps();
            
            $table->unique(['related_type', 'related_id']);
            $table->index('status');
        });

        // 审批记录
        Schema::create('approval_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('instance_id')->comment('审批实例ID');
            $table->unsignedBigInteger('approver_id')->comment('审批人');
            $table->tinyInteger('action')->comment('操作: 1通过 2驳回 3转审 4撤回');
            $table->text('comment')->nullable()->comment('审批意见');
            $table->timestamp('create_time')->useCurrent()->comment('操作时间');
            
            $table->foreign('instance_id')->on('approval_instances')->references('id')->onDelete('cascade');
        });

        // 审批委托表
        Schema::create('approval_delegates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('delegator_id')->comment('委托人');
            $table->unsignedBigInteger('delegate_id')->comment('被委托人');
            $table->date('start_date')->comment('委托开始日期');
            $table->date('end_date')->comment('委托结束日期');
            $table->string('module', 32)->nullable()->comment('委托模块');
            $table->tinyInteger('status')->default(1)->comment('状态');
            $table->timestamps();
            
            $table->index(['delegator_id', 'start_date', 'end_date']);
        });

        // 系统配置表
        Schema::create('system_configs', function (Blueprint $table) {
            $table->id();
            $table->string('group', 32)->comment('配置分组');
            $table->string('key', 64)->comment('配置键');
            $table->text('value')->nullable()->comment('配置值');
            $table->string('description', 255)->nullable()->comment('说明');
            $table->timestamps();
            
            $table->unique(['group', 'key']);
        });

        // 站内消息表
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->tinyInteger('type')->comment('类型: 1系统通知 2审批提醒 3库存预警');
            $table->string('title', 128)->comment('标题');
            $table->text('content')->nullable()->comment('内容');
            $table->unsignedBigInteger('receiver_id')->comment('接收人');
            $table->tinyInteger('is_read')->default(0)->comment('已读');
            $table->timestamps();
            
            $table->foreign('receiver_id')->on('employees')->references('id');
            $table->index('is_read');
        });

        // 打印模板表
        Schema::create('print_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name', 128)->comment('模板名称');
            $table->tinyInteger('type')->comment('类型');
            $table->text('content')->comment('模板内容');
            $table->tinyInteger('is_default')->default(0)->comment('是否默认');
            $table->timestamps();
        });

        // 操作日志表
        Schema::create('operation_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id')->nullable()->comment('操作人');
            $table->string('module', 64)->nullable()->comment('模块');
            $table->string('action', 64)->nullable()->comment('操作类型');
            $table->string('target_type', 32)->nullable()->comment('操作对象类型');
            $table->unsignedBigInteger('target_id')->nullable()->comment('操作对象ID');
            $table->text('description')->nullable()->comment('操作描述');
            $table->string('ip', 45)->nullable()->comment('IP地址');
            $table->timestamp('create_time')->useCurrent()->comment('操作时间');
            
            $table->index('employee_id');
            $table->index('module');
        });

        // 翻译表
        Schema::create('translations', function (Blueprint $table) {
            $table->id();
            $table->string('locale', 10)->comment('语言代码');
            $table->string('entity_type', 32)->comment('实体类型');
            $table->unsignedBigInteger('entity_id')->comment('实体ID');
            $table->string('field_name', 64)->comment('字段名');
            $table->text('translation')->nullable()->comment('翻译内容');
            $table->timestamp('create_time')->useCurrent()->comment('时间');
            
            $table->unique(['locale', 'entity_type', 'entity_id', 'field_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('translations');
        Schema::dropIfExists('operation_logs');
        Schema::dropIfExists('print_templates');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('system_configs');
        Schema::dropIfExists('approval_delegates');
        Schema::dropIfExists('approval_records');
        Schema::dropIfExists('approval_instances');
        Schema::dropIfExists('approval_nodes');
        Schema::dropIfExists('approval_flows');
    }
};
