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
        Schema::create('user_checkin_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->onDelete('cascade')->comment('用户ID');
            $table->integer('total_days')->default(0)->comment('累计签到天数');
            $table->integer('max_consecutive_days')->default(0)->comment('最大连续签到天数');
            $table->integer('current_consecutive_days')->default(0)->comment('当前连续签到天数');
            $table->date('last_checkin_date')->nullable()->comment('最后签到日期');
            $table->integer('makeup_count')->default(0)->comment('本月补签次数');
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_checkin_stats');
    }
};

