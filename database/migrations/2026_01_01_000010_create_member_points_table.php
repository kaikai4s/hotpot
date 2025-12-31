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
        Schema::create('member_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->onDelete('restrict')->comment('用户ID');
            $table->unsignedInteger('total_points')->default(0)->comment('总积分');
            $table->unsignedInteger('available_points')->default(0)->comment('可用积分');
            $table->unsignedInteger('frozen_points')->default(0)->comment('冻结积分');
            $table->enum('level', ['bronze', 'silver', 'gold', 'platinum'])->default('bronze')->comment('等级');
            $table->timestamps();

            $table->index('level');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_points');
    }
};

