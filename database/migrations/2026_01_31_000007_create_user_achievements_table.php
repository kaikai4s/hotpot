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
        Schema::create('user_achievements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->comment('用户ID');
            $table->foreignId('achievement_template_id')->constrained('achievement_templates')->onDelete('cascade')->comment('成就模板ID');
            $table->json('progress')->nullable()->comment('完成进度（JSON格式）');
            $table->timestamp('completed_at')->nullable()->comment('完成时间');
            $table->boolean('reward_issued')->default(false)->comment('奖励是否已发放');
            $table->timestamps();

            $table->unique(['user_id', 'achievement_template_id']);
            $table->index('user_id');
            $table->index('achievement_template_id');
            $table->index('completed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_achievements');
    }
};

