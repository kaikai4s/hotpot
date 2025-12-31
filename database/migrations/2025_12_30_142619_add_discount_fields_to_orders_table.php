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
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('deposit_discount', 10, 2)->default(0)->after('total_amount')->comment('定金抵扣金额');
            $table->decimal('points_discount', 10, 2)->default(0)->after('deposit_discount')->comment('积分抵扣金额');
            $table->integer('points_used')->default(0)->after('points_discount')->comment('使用的积分数');
            $table->decimal('final_amount', 10, 2)->nullable()->after('points_used')->comment('最终支付金额');
            $table->foreignId('reservation_id')->nullable()->constrained('reservations')->onDelete('set null')->after('table_id')->comment('关联预约ID');
            
            $table->index('reservation_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['reservation_id']);
            $table->dropIndex(['reservation_id']);
            $table->dropColumn([
                'deposit_discount',
                'points_discount',
                'points_used',
                'final_amount',
                'reservation_id',
            ]);
        });
    }
};
