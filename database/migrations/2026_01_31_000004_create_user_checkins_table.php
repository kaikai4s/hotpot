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
        Schema::create('user_checkins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->comment('用户ID');
            $table->date('checkin_date')->comment('签到日期');
            $table->integer('consecutive_days')->default(1)->comment('连续签到天数');
            $table->integer('reward_points')->default(0)->comment('奖励积分');
            $table->boolean('is_makeup')->default(false)->comment('是否补签');
            $table->timestamps();

            $table->unique(['user_id', 'checkin_date']);
            $table->index('user_id');
            $table->index('checkin_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_checkins');
    }
};

