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
        // 促销活动表
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 128)->comment('活动名称');
            $table->tinyInteger('type')->comment('活动类型: 1满减 2满赠 3打折 4一口价 5买N送M');
            $table->tinyInteger('trigger_type')->default(1)->comment('触发类型: 1自动应用 2需领取');
            $table->timestamp('start_time')->comment('开始时间');
            $table->timestamp('end_time')->comment('结束时间');
            $table->text('condition_json')->nullable()->comment('触发条件(JSON)');
            $table->text('reward_json')->nullable()->comment('奖励内容(JSON)');
            $table->text('applicable_products')->nullable()->comment('适用范围');
            $table->text('applicable_customers')->nullable()->comment('适用客户');
            $table->text('applicable_channels')->nullable()->comment('适用渠道');
            $table->integer('priority')->default(0)->comment('优先级');
            $table->integer('max_usage')->nullable()->comment('总使用次数上限');
            $table->integer('used_count')->default(0)->comment('已使用次数');
            $table->integer('max_per_customer')->nullable()->comment('每人限用次数');
            $table->tinyInteger('status')->default(1)->comment('状态: 1进行中 0已结束');
            $table->timestamps();
            
            $table->index(['start_time', 'end_time']);
            $table->index('status');
        });

        // 优惠券表
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('name', 128)->comment('优惠券名称');
            $table->tinyInteger('type')->comment('类型: 1满减券 2折扣券 3无门槛券');
            $table->decimal('value', 12, 2)->comment('优惠值');
            $table->decimal('max_discount', 12, 2)->nullable()->comment('最高优惠金额');
            $table->decimal('min_amount', 12, 2)->default(0)->comment('最低消费金额');
            $table->integer('total_quantity')->comment('发行总量');
            $table->integer('used_quantity')->default(0)->comment('已使用数量');
            $table->integer('per_customer_limit')->default(1)->comment('每人限领数量');
            $table->timestamp('start_time')->comment('生效时间');
            $table->timestamp('end_time')->comment('失效时间');
            $table->text('applicable_products')->nullable()->comment('适用范围');
            $table->text('applicable_categories')->nullable()->comment('适用分类');
            $table->text('applicable_channels')->nullable()->comment('适用渠道');
            $table->tinyInteger('status')->default(1)->comment('状态: 1正常 0已失效');
            $table->timestamps();
            
            $table->index(['start_time', 'end_time']);
        });

        // 客户优惠券表
        Schema::create('customer_coupons', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id')->comment('客户ID');
            $table->unsignedBigInteger('coupon_id')->comment('优惠券ID');
            $table->string('code', 32)->nullable()->comment('优惠券码');
            $table->tinyInteger('status')->default(1)->comment('状态: 1未使用 2已使用 3已过期');
            $table->unsignedBigInteger('order_id')->nullable()->comment('使用订单ID');
            $table->timestamp('used_at')->nullable()->comment('使用时间');
            $table->timestamp('received_at')->nullable()->comment('领取时间');
            $table->timestamp('expire_at')->nullable()->comment('过期时间');
            $table->timestamps();
            
            $table->foreign('customer_id')->on('customers')->references('id')->onDelete('cascade');
            $table->foreign('coupon_id')->on('coupons')->references('id')->onDelete('cascade');
            $table->index('status');
        });

        // 订单促销记录表
        Schema::create('order_promotions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id')->comment('订单ID');
            $table->unsignedBigInteger('promotion_id')->nullable()->comment('促销活动ID');
            $table->unsignedBigInteger('coupon_id')->nullable()->comment('优惠券ID');
            $table->unsignedBigInteger('customer_coupon_id')->nullable()->comment('客户优惠券ID');
            $table->string('promotion_name', 128)->nullable()->comment('优惠名称');
            $table->decimal('discount_amount', 12, 2)->comment('优惠金额');
            $table->string('description', 255)->nullable()->comment('优惠描述');
            $table->timestamps();
            
            $table->foreign('order_id')->on('sales_orders')->references('id')->onDelete('cascade');
            $table->foreign('promotion_id')->on('promotions')->references('id');
            $table->foreign('coupon_id')->on('coupons')->references('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_promotions');
        Schema::dropIfExists('customer_coupons');
        Schema::dropIfExists('coupons');
        Schema::dropIfExists('promotions');
    }
};
