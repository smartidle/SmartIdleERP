<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_steps', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('record_id')->comment('审批记录ID');
            $table->unsignedBigInteger('flow_id')->nullable()->comment('流程定义ID');
            $table->unsignedBigInteger('node_id')->nullable()->comment('流程节点ID');
            $table->integer('step_no')->comment('步骤序号');
            $table->unsignedBigInteger('approver_id')->comment('审批人');
            $table->tinyInteger('status')->default(1)->comment('1=待审批 2=已通过 3=已拒绝');
            $table->text('comment')->nullable()->comment('审批意见');
            $table->timestamp('approved_at')->nullable()->comment('审批时间');
            $table->timestamps();

            $table->foreign('record_id')->references('id')->on('approval_records')->onDelete('cascade');
            $table->index(['record_id', 'status']);
            $table->index(['approver_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_steps');
    }
};
