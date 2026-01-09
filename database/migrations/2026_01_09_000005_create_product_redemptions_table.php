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
        Schema::create('product_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('mall_products')->restrictOnDelete();
            $table->unsignedInteger('points_used')->comment('使用积分');
            $table->enum('status', ['pending', 'processing', 'shipped', 'completed', 'cancelled'])
                ->default('pending')->comment('状态');
            $table->json('shipping_address')->nullable()->comment('收货地址（实物商品）');
            $table->string('tracking_number', 100)->nullable()->comment('物流单号');
            $table->timestamp('experience_datetime')->nullable()->comment('体验预约时间');
            $table->enum('experience_status', ['pending', 'used', 'expired'])->nullable()->comment('体验状态');
            $table->text('notes')->nullable()->comment('备注');
            $table->timestamps();
            $table->timestamp('completed_at')->nullable()->comment('完成时间');

            $table->index('user_id');
            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_redemptions');
    }
};
