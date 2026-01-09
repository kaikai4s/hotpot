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
        Schema::create('birthday_privileges_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('year')->comment('年份');
            $table->enum('privilege_type', ['coupon', 'dessert', 'double_points'])->comment('特权类型');
            $table->unsignedBigInteger('reference_id')->nullable()->comment('关联ID（优惠券ID/订单ID等）');
            $table->timestamp('issued_at')->useCurrent()->comment('发放时间');

            $table->unique(['user_id', 'year', 'privilege_type'], 'uk_user_year_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('birthday_privileges_log');
    }
};
