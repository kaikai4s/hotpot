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
        Schema::create('point_statistics', function (Blueprint $table) {
            $table->id();
            $table->date('stat_date')->unique()->comment('统计日期');
            $table->unsignedInteger('total_earned')->default(0)->comment('当日获得积分总数');
            $table->unsignedInteger('total_redeemed')->default(0)->comment('当日兑换积分总数');
            $table->unsignedInteger('total_expired')->default(0)->comment('当日过期积分总数');
            $table->unsignedInteger('active_users')->default(0)->comment('当日活跃用户数');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('point_statistics');
    }
};

