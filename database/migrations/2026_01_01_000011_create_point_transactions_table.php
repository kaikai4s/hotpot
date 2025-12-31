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
        Schema::create('point_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict')->comment('用户ID');
            $table->enum('type', ['earn', 'redeem', 'expire', 'adjust'])->comment('类型');
            $table->integer('points')->comment('积分变动（正数为增加，负数为减少）');
            $table->unsignedInteger('balance_after')->comment('变动后余额');
            $table->string('source_type', 50)->nullable()->comment('来源类型：order, review, redeem等');
            $table->unsignedBigInteger('source_id')->nullable()->comment('来源ID');
            $table->string('description', 255)->nullable()->comment('描述');
            $table->timestamps();

            $table->index('user_id');
            $table->index('type');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('point_transactions');
    }
};

