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
        Schema::create('dish_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->comment('分类名称');
            $table->string('description', 255)->nullable()->comment('分类描述');
            $table->unsignedInteger('sort_order')->default(0)->comment('排序');
            $table->boolean('is_active')->default(true)->comment('是否启用');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dish_categories');
    }
};

