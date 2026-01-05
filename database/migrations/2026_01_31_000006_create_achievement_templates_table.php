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
        Schema::create('achievement_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->comment('成就名称');
            $table->string('description', 500)->nullable()->comment('成就描述');
            $table->string('icon', 255)->nullable()->comment('成就图标URL');
            $table->enum('category', ['consume', 'review', 'invite', 'checkin', 'points'])->comment('成就分类');
            $table->json('target_value')->comment('目标值（JSON格式，如：{"amount": 1000}）');
            $table->integer('reward_points')->default(0)->comment('奖励积分');
            $table->foreignId('reward_coupon_id')->nullable()->constrained('coupons')->onDelete('set null')->comment('奖励优惠券ID');
            $table->boolean('is_active')->default(true)->comment('是否启用');
            $table->integer('sort_order')->default(0)->comment('排序');
            $table->timestamps();

            $table->index('category');
            $table->index('is_active');
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('achievement_templates');
    }
};

