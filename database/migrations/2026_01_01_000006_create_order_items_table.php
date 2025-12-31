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
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade')->comment('订单ID');
            $table->foreignId('dish_id')->constrained('dishes')->onDelete('restrict')->comment('菜品ID');
            $table->unsignedInteger('quantity')->comment('数量');
            $table->decimal('price', 10, 2)->comment('单价');
            $table->decimal('subtotal', 10, 2)->comment('小计');
            $table->timestamps();

            $table->index('order_id');
            $table->index('dish_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};

