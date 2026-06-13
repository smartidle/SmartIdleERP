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
        // 产品分类表
        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parent_id')->default(0)->comment('父分类ID');
            $table->string('name', 128)->comment('分类名称');
            $table->text('name_i18n')->nullable()->comment('多语言名称');
            $table->string('code', 64)->unique()->comment('分类编码');
            $table->tinyInteger('level')->default(1)->comment('层级深度');
            $table->integer('sort')->default(0)->comment('排序');
            $table->tinyInteger('status')->default(1)->comment('状态');
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('parent_id');
        });

        // 产品主表
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('sku_prefix', 16)->nullable()->comment('SKU前缀编码');
            $table->string('name', 255)->comment('产品名称');
            $table->text('name_i18n')->nullable()->comment('多语言名称');
            $table->unsignedBigInteger('category_id')->nullable()->comment('所属分类');
            $table->string('brand', 128)->nullable()->comment('品牌');
            $table->string('base_unit', 32)->default('个')->comment('基础计量单位');
            $table->decimal('base_cost_price', 12, 2)->default(0)->comment('基础成本价');
            $table->decimal('base_sale_price', 12, 2)->default(0)->comment('基础销售价');
            $table->decimal('base_wholesale_price', 12, 2)->default(0)->comment('基础批发价');
            $table->decimal('weight', 10, 3)->nullable()->comment('重量(kg)');
            $table->decimal('length_cm', 10, 2)->nullable()->comment('长度(cm)');
            $table->decimal('width_cm', 10, 2)->nullable()->comment('宽度(cm)');
            $table->decimal('height_cm', 10, 2)->nullable()->comment('高度(cm)');
            $table->decimal('volume_m3', 12, 6)->nullable()->comment('体积(立方米)');
            $table->decimal('min_stock', 10, 2)->default(0)->comment('最低库存预警值');
            $table->decimal('max_stock', 10, 2)->default(0)->comment('最高库存上限');
            $table->integer('shelf_life_days')->nullable()->comment('保质期(天)');
            $table->integer('min_pack_qty')->default(1)->comment('最小包装数量');
            $table->string('image', 512)->nullable()->comment('主图URL');
            $table->text('images')->nullable()->comment('图片集(JSON数组)');
            $table->text('description')->nullable()->comment('产品描述');
            $table->text('description_i18n')->nullable()->comment('多语言描述');
            $table->tinyInteger('is_bom')->default(0)->comment('是否为BOM成品');
            $table->tinyInteger('has_spec')->default(0)->comment('是否有规格');
            $table->tinyInteger('status')->default(1)->comment('状态: 1启用 0停用');
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('category_id');
            $table->index('status');
        });

        // 规格属性定义表
        Schema::create('product_specs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id')->comment('所属产品ID');
            $table->string('spec_name', 32)->comment('规格名称');
            $table->text('spec_values')->comment('规格值列表(JSON)');
            $table->tinyInteger('is_color')->default(0)->comment('是否颜色规格');
            $table->tinyInteger('is_size')->default(0)->comment('是否尺码规格');
            $table->tinyInteger('spec_type')->default(1)->comment('规格类型: 1选项 2输入');
            $table->tinyInteger('spec_image_mode')->default(0)->comment('规格图片模式');
            $table->integer('sort')->default(0)->comment('排序');
            $table->timestamps();
            
            $table->foreign('product_id')->on('products')->references('id')->onDelete('cascade');
            $table->index('product_id');
        });

        // SKU规格表
        Schema::create('product_skus', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id')->comment('所属产品ID');
            $table->string('sku_code', 64)->unique()->comment('SKU编码');
            $table->string('barcode', 64)->nullable()->index()->comment('条形码');
            $table->text('spec_combination')->comment('规格组合(JSON)');
            $table->string('spec_hash', 64)->nullable()->index()->comment('规格组合Hash');
            $table->decimal('cost_price', 12, 2)->nullable()->comment('SKU成本价');
            $table->decimal('sale_price', 12, 2)->nullable()->comment('SKU销售价');
            $table->decimal('wholesale_price', 12, 2)->nullable()->comment('SKU批发价');
            $table->string('image', 512)->nullable()->comment('SKU图片');
            $table->decimal('weight', 10, 3)->nullable()->comment('重量(kg)');
            $table->tinyInteger('status')->default(1)->comment('状态: 1启用 0停用');
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('product_id')->on('products')->references('id')->onDelete('cascade');
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_skus');
        Schema::dropIfExists('product_specs');
        Schema::dropIfExists('products');
        Schema::dropIfExists('product_categories');
    }
};
