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
     * 
     * 移除 min_people 和 max_people 字段
     * 团购是指多个菜品组合成套餐，而不是多人一起购买
     */
    public function up(): void
    {
        Schema::table('group_buys', function (Blueprint $table) {
            $table->dropColumn(['min_people', 'max_people']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('group_buys', function (Blueprint $table) {
            $table->unsignedInteger('min_people')->default(1)->after('group_price')->comment('最少成团人数');
            $table->unsignedInteger('max_people')->nullable()->after('min_people')->comment('最多参团人数（null表示不限制）');
        });
    }
};
