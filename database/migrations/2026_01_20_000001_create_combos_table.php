<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

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
        Schema::create('combos', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->comment('套餐名称');
            $table->text('description')->nullable()->comment('套餐描述');
            $table->string('image_url', 255)->nullable()->comment('套餐图片');
            $table->decimal('price', 10, 2)->comment('套餐价格');
            $table->unsignedInteger('stock')->default(0)->comment('库存（0表示不限制）');
            $table->unsignedInteger('sold_count')->default(0)->comment('已售数量');
            $table->boolean('is_active')->default(true)->comment('是否启用');
            $table->integer('sort_order')->default(0)->comment('排序（数字越小越靠前）');
            $table->timestamps();

            $table->index('is_active');
            $table->index('sort_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('combos');
    }
};

