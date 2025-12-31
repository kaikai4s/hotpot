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
        Schema::create('configurations', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique()->comment('配置键名');
            $table->text('value')->nullable()->comment('配置值');
            $table->string('type', 50)->default('string')->comment('配置类型：string, integer, boolean, json');
            $table->string('group', 50)->default('general')->comment('配置分组');
            $table->string('label', 100)->comment('配置标签');
            $table->text('description')->nullable()->comment('配置描述');
            $table->integer('sort_order')->default(0)->comment('排序');
            $table->boolean('is_public')->default(false)->comment('是否公开（前台可访问）');
            $table->timestamps();

            $table->index('key');
            $table->index('group');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('configurations');
    }
};
