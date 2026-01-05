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
        Schema::table('reservations', function (Blueprint $table) {
            $table->boolean('is_viewed')
                  ->default(false)
                  ->after('arrived_at')
                  ->comment('管理员是否已查看（用于定金管理）');
            $table->timestamp('viewed_at')
                  ->nullable()
                  ->after('is_viewed')
                  ->comment('管理员查看时间');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn(['is_viewed', 'viewed_at']);
        });
    }
};
