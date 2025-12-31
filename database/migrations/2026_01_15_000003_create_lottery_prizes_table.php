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
        Schema::create('lottery_prizes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lottery_activity_id')->comment('抽奖活动ID');
            $table->string('name', 100)->comment('奖品名称');
            $table->text('description')->nullable()->comment('奖品描述');
            $table->string('image_url')->nullable()->comment('奖品图片');
            $table->enum('prize_type', ['coupon', 'points', 'dish'])->comment('奖品类型：coupon优惠券, points积分, dish菜品');
            $table->unsignedBigInteger('prize_id')->nullable()->comment('奖品ID（优惠券ID、菜品ID等）');
            $table->unsignedInteger('prize_value')->nullable()->comment('奖品值（积分数量等）');
            $table->unsignedInteger('probability')->comment('中奖概率（万分之几，如100表示1%）');
            $table->unsignedInteger('stock')->default(0)->comment('库存（0表示不限制）');
            $table->unsignedInteger('daily_stock')->default(0)->comment('每日库存（0表示不限制）');
            $table->integer('sort_order')->default(0)->comment('排序');
            $table->boolean('is_active')->default(true)->comment('是否启用');
            $table->timestamps();

            $table->foreign('lottery_activity_id')->references('id')->on('lottery_activities')->onDelete('cascade');
            $table->index('lottery_activity_id');
            $table->index('is_active');
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lottery_prizes');
    }
};

