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
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('user_coupon_id')->nullable()->after('points_used')->comment('使用的优惠券ID');
            $table->decimal('coupon_discount', 10, 2)->default(0)->after('user_coupon_id')->comment('优惠券折扣金额');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['user_coupon_id', 'coupon_discount']);
        });
    }
};

