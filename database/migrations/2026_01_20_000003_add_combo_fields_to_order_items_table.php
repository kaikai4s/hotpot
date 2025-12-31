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
        Schema::table('order_items', function (Blueprint $table) {
            $table->enum('type', ['dish', 'combo'])->default('dish')->after('order_id')->comment('类型：dish菜品, combo套餐');
            $table->foreignId('combo_id')->nullable()->after('dish_id')->constrained('combos')->onDelete('restrict')->comment('套餐ID（当type为combo时）');
            $table->decimal('original_total', 10, 2)->nullable()->after('subtotal')->comment('原价总计（套餐中所有菜品的原价总和）');
            $table->decimal('savings', 10, 2)->nullable()->after('original_total')->comment('优惠金额（原价总计-套餐价格）');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['combo_id']);
            $table->dropColumn(['type', 'combo_id', 'original_total', 'savings']);
        });
    }
};

