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
        Schema::create('group_buy_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_buy_id')->constrained('group_buys')->onDelete('cascade')->comment('团购ID');
            $table->foreignId('dish_id')->constrained('dishes')->onDelete('cascade')->comment('菜品ID');
            $table->unsignedInteger('quantity')->default(1)->comment('菜品数量');
            $table->integer('sort_order')->default(0)->comment('排序');
            $table->timestamps();

            $table->index('group_buy_id');
            $table->index('dish_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('group_buy_items');
    }
};
