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
        Schema::create('lottery_activities', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->comment('抽奖活动名称');
            $table->text('description')->nullable()->comment('活动描述');
            $table->string('image_url')->nullable()->comment('活动图片');
            $table->timestamp('start_time')->comment('开始时间');
            $table->timestamp('end_time')->comment('结束时间');
            $table->unsignedInteger('daily_limit')->default(0)->comment('每日抽奖次数限制（0表示不限制）');
            $table->unsignedInteger('total_limit')->default(0)->comment('总抽奖次数限制（0表示不限制）');
            $table->unsignedInteger('points_cost')->default(0)->comment('每次抽奖消耗积分（0表示免费）');
            $table->boolean('is_active')->default(true)->comment('是否启用');
            $table->integer('sort_order')->default(0)->comment('排序');
            $table->timestamps();

            $table->index('is_active');
            $table->index(['start_time', 'end_time']);
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lottery_activities');
    }
};

