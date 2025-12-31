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
        Schema::create('user_coupons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->comment('用户ID');
            $table->foreignId('coupon_id')->constrained('coupons')->onDelete('restrict')->comment('优惠券ID');
            $table->string('code', 32)->unique()->comment('优惠券码');
            $table->enum('status', ['unused', 'used', 'expired'])->default('unused')->comment('状态');
            $table->timestamp('used_at')->nullable()->comment('使用时间');
            $table->timestamp('expires_at')->nullable()->comment('过期时间');
            $table->timestamps();

            $table->index('user_id');
            $table->index('coupon_id');
            $table->index('status');
            $table->index('code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_coupons');
    }
};

