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
        Schema::create('user_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inviter_id')->constrained('users')->onDelete('cascade')->comment('邀请人ID');
            $table->foreignId('invitee_id')->nullable()->constrained('users')->onDelete('set null')->comment('被邀请人ID');
            $table->string('invite_code', 32)->unique()->comment('邀请码（格式：INV{user_id}{6位随机}）');
            $table->enum('status', ['pending', 'registered', 'completed'])->default('pending')->comment('状态：pending待注册，registered已注册，completed已完成首次消费');
            $table->timestamp('registered_at')->nullable()->comment('注册时间');
            $table->timestamp('first_order_at')->nullable()->comment('首次消费时间');
            $table->boolean('reward_issued')->default(false)->comment('奖励是否已发放');
            $table->timestamps();

            $table->index('inviter_id');
            $table->index('invitee_id');
            $table->index('status');
            $table->index('invite_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_invitations');
    }
};

