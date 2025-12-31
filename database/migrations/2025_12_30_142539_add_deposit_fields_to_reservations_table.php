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
        Schema::table('reservations', function (Blueprint $table) {
            $table->decimal('deposit_amount', 10, 2)->default(0)->after('special_requests')->comment('定金金额');
            $table->enum('deposit_status', ['unpaid', 'paid', 'refunded', 'forfeited'])->default('unpaid')->after('deposit_amount')->comment('定金状态');
            $table->timestamp('deposit_paid_at')->nullable()->after('deposit_status')->comment('定金支付时间');
            $table->timestamp('deposit_refunded_at')->nullable()->after('deposit_paid_at')->comment('定金退还时间');
            $table->string('deposit_transaction_id', 64)->nullable()->after('deposit_refunded_at')->comment('定金交易ID');
            $table->json('deposit_data')->nullable()->after('deposit_transaction_id')->comment('定金支付数据');
            $table->timestamp('arrived_at')->nullable()->after('deposit_refunded_at')->comment('到达时间');
            $table->foreignId('order_id')->nullable()->constrained('orders')->onDelete('set null')->after('arrived_at')->comment('关联订单ID（用于定金抵扣）');
            
            $table->index('deposit_status');
            $table->index('order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropForeign(['order_id']);
            $table->dropIndex(['deposit_status']);
            $table->dropIndex(['order_id']);
            $table->dropColumn([
                'deposit_amount',
                'deposit_status',
                'deposit_paid_at',
                'deposit_refunded_at',
                'deposit_transaction_id',
                'deposit_data',
                'arrived_at',
                'order_id',
            ]);
        });
    }
};
