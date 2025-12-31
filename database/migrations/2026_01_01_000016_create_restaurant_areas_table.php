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
        Schema::create('restaurant_areas', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->comment('区域名称，如：窗边区域');
            $table->string('type', 20)->comment('区域类型：window, corner, center, custom');
            $table->json('boundaries')->comment('区域边界，格式：{"x1": 0, "y1": 0, "x2": 200, "y2": 500} 或 {"x": 0, "y": 0, "width": 200, "height": 500}');
            $table->string('color', 20)->nullable()->comment('区域颜色（十六进制）');
            $table->unsignedInteger('sort_order')->default(0)->comment('排序');
            $table->boolean('is_active')->default(true)->comment('是否启用');
            $table->timestamps();

            $table->index('type');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurant_areas');
    }
};

