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
    public function up(): void
    {
        Schema::create('mall_products', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->comment('商品名称');
            $table->text('description')->nullable()->comment('商品描述');
            $table->string('image_url', 255)->nullable()->comment('商品图片');
            $table->enum('type', ['physical', 'experience', 'coupon'])->comment('商品类型');
            $table->unsignedInteger('points_required')->comment('所需积分');
            $table->unsignedInteger('stock')->default(0)->comment('库存');
            $table->unsignedInteger('per_user_limit')->nullable()->comment('每人限兑数量');
            $table->enum('status', ['active', 'inactive', 'sold_out'])->default('active')->comment('状态');
            $table->unsignedInteger('valid_days')->nullable()->comment('体验券有效天数');
            $table->json('experience_config')->nullable()->comment('体验类商品配置（时间段等）');
            $table->unsignedInteger('sort_order')->default(0)->comment('排序');
            $table->timestamps();

            $table->index('type');
            $table->index('status');
            $table->index('points_required');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mall_products');
    }
};
