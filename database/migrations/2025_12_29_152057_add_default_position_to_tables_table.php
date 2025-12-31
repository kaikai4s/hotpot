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
        Schema::table('tables', function (Blueprint $table) {
            $table->unsignedInteger('default_position_x')->nullable()->after('position_y')->comment('默认X坐标（用于重置位置）');
            $table->unsignedInteger('default_position_y')->nullable()->after('default_position_x')->comment('默认Y坐标（用于重置位置）');
        });
    }

    public function down(): void
    {
        Schema::table('tables', function (Blueprint $table) {
            $table->dropColumn(['default_position_x', 'default_position_y']);
        });
    }
};
