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
        Schema::create('point_levels', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->comment('段位名称');
            $table->string('code', 50)->unique()->comment('段位代码（唯一标识）');
            $table->unsignedInteger('min_points')->default(0)->comment('达到该段位所需的最低积分');
            $table->enum('discount_type', ['none', 'percentage', 'fixed'])->default('none')->comment('折扣类型：none无折扣，percentage百分比折扣，fixed固定金额折扣');
            $table->decimal('discount_value', 10, 2)->default(0)->comment('折扣值（百分比0-100，固定金额为金额）');
            $table->decimal('max_discount_amount', 10, 2)->nullable()->comment('最大折扣金额（仅百分比折扣时有效）');
            $table->decimal('min_order_amount', 10, 2)->default(0)->comment('最低订单金额（达到此金额才能享受折扣）');
            $table->boolean('is_active')->default(true)->comment('是否启用');
            $table->integer('sort_order')->default(0)->comment('排序（数字越小越靠前）');
            $table->string('description', 500)->nullable()->comment('段位描述');
            $table->string('icon', 100)->nullable()->comment('图标（图标类名或URL）');
            $table->string('color', 20)->nullable()->comment('颜色（用于显示）');
            $table->timestamps();

            $table->index('is_active');
            $table->index('sort_order');
            $table->index('min_points');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('point_levels');
    }
};

