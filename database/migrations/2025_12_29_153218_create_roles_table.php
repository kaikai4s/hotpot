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
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique()->comment('角色名称，如：super_admin');
            $table->string('display_name', 100)->comment('显示名称，如：超级管理员');
            $table->text('description')->nullable()->comment('角色描述');
            $table->boolean('is_system')->default(false)->comment('是否系统角色（不可删除）');
            $table->unsignedInteger('sort_order')->default(0)->comment('排序');
            $table->timestamps();

            $table->index('is_system');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
