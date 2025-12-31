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
            $table->string('user_level_code', 50)->nullable()->after('coupon_discount')->comment('用户下单时的段位代码');
            $table->decimal('level_discount', 10, 2)->default(0)->after('user_level_code')->comment('段位折扣金额');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['user_level_code', 'level_discount']);
        });
    }
};

