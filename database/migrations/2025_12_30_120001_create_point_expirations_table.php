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
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('point_expirations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict')->comment('用户ID');
            $table->foreignId('transaction_id')->constrained('point_transactions')->onDelete('restrict')->comment('交易记录ID');
            $table->integer('points')->comment('过期积分数量');
            $table->timestamp('expire_at')->comment('过期时间');
            $table->timestamp('expired_at')->nullable()->comment('实际过期时间');
            $table->enum('status', ['pending', 'expired', 'cancelled'])->default('pending')->comment('状态');
            $table->timestamps();

            $table->index('user_id');
            $table->index('expire_at');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('point_expirations');
    }
};

