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
        // 供应商表
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique()->comment('供应商编码');
            $table->string('name', 255)->comment('供应商名称');
            $table->string('contact_person', 128)->nullable()->comment('联系人');
            $table->string('phone', 32)->nullable()->comment('电话');
            $table->string('mobile', 32)->nullable()->comment('手机');
            $table->string('email', 128)->nullable()->comment('邮箱');
            $table->text('address')->nullable()->comment('地址');
            $table->string('country', 64)->nullable()->comment('国家');
            $table->string('city', 64)->nullable()->comment('城市');
            $table->string('bank_name', 128)->nullable()->comment('开户银行');
            $table->string('bank_account', 64)->nullable()->comment('银行账号');
            $table->string('bank_swift', 32)->nullable()->comment('SWIFT代码');
            $table->tinyInteger('payment_terms')->default(2)->comment('付款条件');
            $table->integer('lead_time')->default(0)->comment('交货周期(天)');
            $table->tinyInteger('rating')->default(3)->comment('评级: 1-5星');
            $table->tinyInteger('cooperation_status')->default(1)->comment('合作状态');
            $table->text('remark')->nullable()->comment('备注');
            $table->tinyInteger('status')->default(1)->comment('状态');
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('status');
        });

        // 供应商产品报价表
        Schema::create('supplier_products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('supplier_id')->comment('供应商ID');
            $table->unsignedBigInteger('product_id')->comment('产品ID');
            $table->unsignedBigInteger('sku_id')->nullable()->comment('SKU ID');
            $table->decimal('supply_price', 12, 2)->comment('供应价格');
            $table->decimal('min_order_qty', 10, 2)->default(1)->comment('最小订购量');
            $table->integer('delivery_days')->default(0)->comment('交货天数');
            $table->tinyInteger('is_primary')->default(0)->comment('是否首选供应商');
            $table->date('valid_from')->nullable()->comment('报价有效期起');
            $table->date('valid_to')->nullable()->comment('报价有效期止');
            $table->timestamps();
            
            $table->foreign('supplier_id')->on('suppliers')->references('id')->onDelete('cascade');
            $table->unique(['supplier_id', 'product_id', 'sku_id']);
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_products');
        Schema::dropIfExists('suppliers');
    }
};
