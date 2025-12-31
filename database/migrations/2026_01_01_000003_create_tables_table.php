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
        Schema::create('tables', function (Blueprint $table) {
            $table->id();
            $table->string('name', 20)->unique()->comment('桌位名称，如A01');
            $table->unsignedInteger('capacity')->comment('容纳人数');
            $table->enum('type', ['window', 'corner', 'center'])->comment('桌位类型');
            $table->unsignedInteger('position_x')->nullable()->comment('X坐标（用于可视化）');
            $table->unsignedInteger('position_y')->nullable()->comment('Y坐标（用于可视化）');
            $table->enum('status', ['available', 'reserved', 'occupied', 'maintenance'])->default('available')->comment('状态');
            $table->timestamps();

            $table->index('status');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tables');
    }
};

