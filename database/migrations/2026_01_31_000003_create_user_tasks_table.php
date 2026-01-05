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
        Schema::create('user_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->comment('用户ID');
            $table->foreignId('task_template_id')->constrained('task_templates')->onDelete('cascade')->comment('任务模板ID');
            $table->enum('status', ['pending', 'in_progress', 'completed', 'expired'])->default('pending')->comment('状态');
            $table->json('progress')->nullable()->comment('完成进度（JSON格式，如：{"current": 2, "target": 5}）');
            $table->timestamp('completed_at')->nullable()->comment('完成时间');
            $table->boolean('reward_issued')->default(false)->comment('奖励是否已发放');
            $table->timestamp('expires_at')->nullable()->comment('过期时间');
            $table->timestamps();

            $table->index('user_id');
            $table->index('task_template_id');
            $table->index('status');
            $table->index('expires_at');
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_tasks');
    }
};

