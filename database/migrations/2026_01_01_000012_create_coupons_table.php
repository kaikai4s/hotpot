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
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->comment('优惠券名称');
            $table->enum('type', ['discount', 'cash', 'points'])->comment('类型：discount折扣，cash现金，points积分');
            $table->decimal('value', 10, 2)->nullable()->comment('面额或折扣值');
            $table->unsignedInteger('points_required')->default(0)->comment('所需积分');
            $table->unsignedInteger('stock')->default(0)->comment('库存');
            $table->timestamp('valid_from')->nullable()->comment('有效期开始');
            $table->timestamp('valid_to')->nullable()->comment('有效期结束');
            $table->boolean('is_active')->default(true)->comment('是否启用');
            $table->timestamps();

            $table->index('is_active');
            $table->index(['valid_from', 'valid_to']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};

