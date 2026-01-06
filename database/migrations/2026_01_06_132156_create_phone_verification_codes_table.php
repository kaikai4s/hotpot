<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

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
        Schema::create('phone_verification_codes', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 20)->index()->comment('手机号');
            $table->string('code', 6)->comment('验证码');
            $table->string('type', 20)->default('login')->comment('类型：login登录, register注册, reset_password重置密码');
            $table->boolean('is_used')->default(false)->comment('是否已使用');
            $table->timestamp('expires_at')->comment('过期时间');
            $table->timestamp('used_at')->nullable()->comment('使用时间');
            $table->ipAddress('ip_address')->nullable()->comment('IP地址');
            $table->timestamps();

            $table->index(['phone', 'code', 'is_used']);
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('phone_verification_codes');
    }
};
