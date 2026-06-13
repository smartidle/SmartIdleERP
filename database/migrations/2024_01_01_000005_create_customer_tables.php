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
        // 客户表
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique()->comment('客户编码');
            $table->string('name', 255)->comment('客户名称');
            $table->tinyInteger('level')->default(1)->comment('客户等级: 1普通 2银卡 3金卡 4钻石');
            $table->decimal('credit_limit', 12, 2)->default(0)->comment('信用额度');
            $table->decimal('current_debt', 12, 2)->default(0)->comment('当前欠款金额');
            $table->decimal('discount_rate', 5, 2)->default(0)->comment('默认折扣率(%)');
            $table->tinyInteger('payment_terms')->default(1)->comment('付款条件');
            $table->string('contact_person', 128)->nullable()->comment('联系人');
            $table->string('phone', 32)->nullable()->comment('电话');
            $table->string('mobile', 32)->nullable()->comment('手机');
            $table->string('email', 128)->nullable()->comment('邮箱');
            $table->text('address')->nullable()->comment('地址');
            $table->string('country', 64)->nullable()->comment('国家');
            $table->string('city', 64)->nullable()->comment('城市');
            $table->string('tax_number', 64)->nullable()->comment('税号');
            $table->string('source', 64)->nullable()->comment('客户来源');
            $table->date('birthday')->nullable()->comment('联系人生日');
            $table->text('remark')->nullable()->comment('备注');
            $table->tinyInteger('status')->default(1)->comment('状态: 1正常 0冻结');
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('level');
            $table->index('status');
        });

        // 客户收货地址
        Schema::create('customer_addresses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id')->comment('客户ID');
            $table->text('address')->comment('收货地址');
            $table->string('contact_person', 128)->nullable()->comment('收货人');
            $table->string('phone', 32)->nullable()->comment('收货人电话');
            $table->tinyInteger('is_default')->default(0)->comment('是否默认地址');
            $table->timestamps();
            
            $table->foreign('customer_id')->on('customers')->references('id')->onDelete('cascade');
            $table->index('customer_id');
        });

        // 客户专属价格表
        Schema::create('customer_prices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id')->comment('客户ID');
            $table->unsignedBigInteger('product_id')->nullable()->comment('产品ID');
            $table->unsignedBigInteger('sku_id')->nullable()->comment('SKU ID');
            $table->decimal('price', 12, 2)->comment('专属价格');
            $table->decimal('discount_rate', 5, 2)->nullable()->comment('专属折扣率');
            $table->date('valid_from')->nullable()->comment('生效日期');
            $table->date('valid_to')->nullable()->comment('失效日期');
            $table->timestamps();
            
            $table->foreign('customer_id')->on('customers')->references('id')->onDelete('cascade');
            $table->index(['customer_id', 'product_id']);
        });

        // 客户联系记录
        Schema::create('customer_contact_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id')->comment('客户ID');
            $table->unsignedBigInteger('employee_id')->nullable()->comment('跟进员工');
            $table->tinyInteger('contact_type')->comment('联系类型');
            $table->date('contact_date')->comment('联系日期');
            $table->text('content')->nullable()->comment('联系内容');
            $table->date('next_follow_date')->nullable()->comment('下次跟进日期');
            $table->timestamps();
            
            $table->foreign('customer_id')->on('customers')->references('id')->onDelete('cascade');
            $table->index('customer_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_contact_logs');
        Schema::dropIfExists('customer_prices');
        Schema::dropIfExists('customer_addresses');
        Schema::dropIfExists('customers');
    }
};
