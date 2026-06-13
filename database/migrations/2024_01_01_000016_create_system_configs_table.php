<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Check if table exists and modify it
        if (!Schema::hasTable('system_configs')) {
            Schema::create('system_configs', function (Blueprint $table) {
                $table->id();
                $table->string('group', 32)->default('general')->comment('配置分组');
                $table->string('key', 64)->unique()->comment('配置键');
                $table->string('value', 500)->nullable()->comment('配置值');
                $table->string('type', 32)->default('text')->comment('类型: text, number, boolean, image, select');
                $table->string('label', 128)->comment('显示名称');
                $table->string('description', 255)->nullable()->comment('描述');
                $table->text('options')->nullable()->comment('选项JSON(select类型用)');
                $table->integer('sort')->default(0)->comment('排序');
                $table->tinyInteger('status')->default(1)->comment('状态: 0禁用 1启用');
                $table->timestamps();
                
                $table->index('group');
                $table->index('key');
            });
        } else {
            // Table exists, modify it
            Schema::table('system_configs', function (Blueprint $table) {
                if (!Schema::hasColumn('system_configs', 'type')) {
                    $table->string('type', 32)->default('text')->after('value');
                }
                if (!Schema::hasColumn('system_configs', 'label')) {
                    $table->string('label', 128)->after('type');
                }
                if (!Schema::hasColumn('system_configs', 'options')) {
                    $table->text('options')->nullable()->after('description');
                }
                if (!Schema::hasColumn('system_configs', 'sort')) {
                    $table->integer('sort')->default(0)->after('options');
                }
                if (!Schema::hasColumn('system_configs', 'status')) {
                    $table->tinyInteger('status')->default(1)->after('sort');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('system_configs');
    }
};
