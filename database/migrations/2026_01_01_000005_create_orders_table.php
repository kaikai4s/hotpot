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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_no', 32)->unique()->comment('订单号');
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict')->comment('用户ID');
            $table->foreignId('table_id')->nullable()->constrained('tables')->onDelete('set null')->comment('桌位ID');
            $table->decimal('total_amount', 10, 2)->comment('订单总金额');
            $table->enum('status', ['pending', 'paid', 'completed', 'cancelled'])->default('pending')->comment('订单状态');
            $table->timestamp('paid_at')->nullable()->comment('支付时间');
            $table->timestamp('completed_at')->nullable()->comment('完成时间');
            $table->timestamps();

            $table->index('user_id');
            $table->index('order_no');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};

