<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_day_configs', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('day_of_month')->default(8)->comment('每月几号');
            $table->boolean('is_enabled')->default(true)->comment('是否启用');
            $table->decimal('base_discount', 3, 2)->default(0.88)->comment('基础折扣');
            $table->decimal('points_bonus_rate', 3, 2)->default(0.50)->comment('积分加成比例');
            $table->json('discount_by_level')->nullable()->comment('各等级折扣配置');
            $table->unsignedTinyInteger('current_month_override')->nullable()->comment('当月临时调整日期');
            $table->timestamps();
        });

        // 插入默认配置
        DB::table('member_day_configs')->insert([
            'day_of_month' => 8,
            'is_enabled' => true,
            'base_discount' => 0.88,
            'points_bonus_rate' => 0.50,
            'discount_by_level' => json_encode([
                'bronze' => 0.90,
                'silver' => 0.88,
                'gold' => 0.85,
                'platinum' => 0.80,
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('member_day_configs');
    }
};
