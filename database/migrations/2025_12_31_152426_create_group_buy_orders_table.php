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
        Schema::create('group_buy_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_buy_id')->constrained('group_buys')->onDelete('restrict')->comment('团购ID');
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict')->comment('用户ID');
            $table->foreignId('order_id')->nullable()->constrained('orders')->onDelete('set null')->comment('关联订单ID');
            $table->unsignedInteger('quantity')->default(1)->comment('购买数量');
            $table->decimal('total_price', 10, 2)->comment('总价');
            $table->enum('status', ['pending', 'paid', 'used', 'expired', 'refunded'])->default('pending')->comment('状态：pending待支付, paid已支付, used已使用, expired已过期, refunded已退款');
            $table->timestamp('paid_at')->nullable()->comment('支付时间');
            $table->timestamp('used_at')->nullable()->comment('使用时间');
            $table->timestamp('expires_at')->nullable()->comment('过期时间');
            $table->timestamps();

            $table->index('group_buy_id');
            $table->index('user_id');
            $table->index('order_id');
            $table->index('status');
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('group_buy_orders');
    }
};
