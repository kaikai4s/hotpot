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
        Schema::create('combo_dishes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('combo_id')->constrained('combos')->onDelete('cascade')->comment('套餐ID');
            $table->foreignId('dish_id')->constrained('dishes')->onDelete('cascade')->comment('菜品ID');
            $table->unsignedInteger('quantity')->default(1)->comment('菜品数量');
            $table->integer('sort_order')->default(0)->comment('排序');
            $table->timestamps();

            $table->index('combo_id');
            $table->index('dish_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('combo_dishes');
    }
};

