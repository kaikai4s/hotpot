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
        Schema::create('lottery_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->comment('用户ID');
            $table->unsignedBigInteger('lottery_activity_id')->comment('抽奖活动ID');
            $table->unsignedBigInteger('lottery_prize_id')->nullable()->comment('中奖奖品ID（未中奖为null）');
            $table->enum('prize_type', ['coupon', 'points', 'dish', 'none'])->default('none')->comment('奖品类型');
            $table->unsignedBigInteger('prize_id')->nullable()->comment('奖品ID（优惠券ID、菜品ID等）');
            $table->unsignedInteger('prize_value')->nullable()->comment('奖品值');
            $table->boolean('is_winner')->default(false)->comment('是否中奖');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('lottery_activity_id')->references('id')->on('lottery_activities')->onDelete('cascade');
            $table->foreign('lottery_prize_id')->references('id')->on('lottery_prizes')->onDelete('set null');
            
            $table->index('user_id');
            $table->index('lottery_activity_id');
            $table->index('created_at');
            $table->index(['user_id', 'lottery_activity_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lottery_records');
    }
};

