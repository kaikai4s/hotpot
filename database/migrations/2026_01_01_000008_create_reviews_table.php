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
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict')->comment('用户ID');
            $table->foreignId('order_id')->constrained('orders')->onDelete('restrict')->comment('订单ID');
            $table->foreignId('dish_id')->constrained('dishes')->onDelete('restrict')->comment('菜品ID');
            $table->unsignedTinyInteger('rating')->comment('评分：1-5');
            $table->text('content')->nullable()->comment('评价内容');
            $table->json('images')->nullable()->comment('图片URL数组');
            $table->json('tags')->nullable()->comment('标签数组');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->comment('状态');
            $table->unsignedInteger('helpful_count')->default(0)->comment('有用数');
            $table->timestamp('reviewed_at')->nullable()->comment('审核时间');
            $table->timestamps();

            $table->index('user_id');
            $table->index('dish_id');
            $table->index('order_id');
            $table->index('status');
            $table->index('rating');
            $table->unique(['user_id', 'order_id', 'dish_id'], 'uk_user_order_dish');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};

