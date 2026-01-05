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
        Schema::create('user_shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->comment('用户ID');
            $table->enum('share_type', ['review', 'order', 'achievement', 'task'])->comment('分享类型');
            $table->unsignedBigInteger('share_content_id')->comment('分享内容ID');
            $table->enum('share_platform', ['wechat', 'moments'])->default('moments')->comment('分享平台');
            $table->integer('reward_points')->default(0)->comment('奖励积分');
            $table->boolean('reward_issued')->default(false)->comment('奖励是否已发放');
            $table->timestamps();

            $table->index('user_id');
            $table->index(['share_type', 'share_content_id']);
            $table->index('created_at');
            // 防止同一天重复分享同一内容获得奖励
            $table->index(['user_id', 'share_type', 'share_content_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_shares');
    }
};

