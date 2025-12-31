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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('user_type', 20)->nullable()->comment('用户类型：user, admin');
            $table->unsignedBigInteger('user_id')->nullable()->comment('用户ID');
            $table->string('action', 50)->comment('操作类型');
            $table->string('model_type', 100)->nullable()->comment('模型类型');
            $table->unsignedBigInteger('model_id')->nullable()->comment('模型ID');
            $table->json('old_values')->nullable()->comment('旧值');
            $table->json('new_values')->nullable()->comment('新值');
            $table->string('ip_address', 45)->nullable()->comment('IP地址');
            $table->text('user_agent')->nullable()->comment('User Agent');
            $table->text('description')->nullable()->comment('描述');
            $table->timestamps();

            $table->index(['user_type', 'user_id']);
            $table->index('action');
            $table->index(['model_type', 'model_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};

