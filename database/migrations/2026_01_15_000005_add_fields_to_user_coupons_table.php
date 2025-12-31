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
        Schema::table('user_coupons', function (Blueprint $table) {
            $table->string('obtained_from', 50)->nullable()->after('code')->comment('获得来源：lottery抽奖, redeem兑换, manual手动发放');
            $table->timestamp('obtained_at')->nullable()->after('obtained_from')->comment('获得时间');
            $table->unsignedBigInteger('order_id')->nullable()->after('used_at')->comment('使用的订单ID');
        });
    }

    public function down(): void
    {
        Schema::table('user_coupons', function (Blueprint $table) {
            $table->dropColumn(['obtained_from', 'obtained_at', 'order_id']);
        });
    }
};

