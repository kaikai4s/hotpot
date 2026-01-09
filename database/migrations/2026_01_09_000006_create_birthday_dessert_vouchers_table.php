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
        Schema::create('birthday_dessert_vouchers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('year')->comment('年份');
            $table->string('code', 32)->unique()->comment('兑换码');
            $table->enum('status', ['unused', 'used', 'expired'])->default('unused')->comment('状态');
            $table->timestamp('expires_at')->comment('过期时间');
            $table->timestamp('used_at')->nullable()->comment('使用时间');
            $table->unsignedBigInteger('order_id')->nullable()->comment('关联订单ID');
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['user_id', 'year'], 'uk_user_year');
            $table->index('code');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('birthday_dessert_vouchers');
    }
};
