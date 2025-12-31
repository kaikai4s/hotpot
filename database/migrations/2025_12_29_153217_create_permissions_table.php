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
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique()->comment('权限名称，如：tables.view');
            $table->string('display_name', 100)->comment('显示名称，如：查看桌位');
            $table->string('group', 50)->comment('权限分组，如：tables, reservations, reviews');
            $table->text('description')->nullable()->comment('权限描述');
            $table->unsignedInteger('sort_order')->default(0)->comment('排序');
            $table->timestamps();

            $table->index('group');
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
