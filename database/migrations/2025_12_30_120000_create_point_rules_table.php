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
        Schema::create('point_rules', function (Blueprint $table) {
            $table->id();
            $table->string('rule_key', 50)->unique()->comment('规则键');
            $table->string('rule_name', 100)->comment('规则名称');
            $table->enum('rule_type', ['earn', 'use', 'expire'])->comment('规则类型：earn获得，use使用，expire过期');
            $table->json('config')->comment('规则配置（JSON格式）');
            $table->boolean('is_active')->default(true)->comment('是否启用');
            $table->integer('sort_order')->default(0)->comment('排序');
            $table->timestamps();

            $table->index('rule_type');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('point_rules');
    }
};

