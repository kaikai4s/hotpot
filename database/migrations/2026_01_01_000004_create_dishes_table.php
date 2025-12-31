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
        Schema::create('dishes', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->comment('菜品名称');
            $table->text('description')->nullable()->comment('菜品描述');
            $table->decimal('price', 10, 2)->comment('价格');
            $table->string('image_url', 255)->nullable()->comment('主图URL');
            $table->foreignId('category_id')->constrained('dish_categories')->onDelete('restrict')->comment('分类ID');
            $table->enum('status', ['available', 'sold_out', 'disabled'])->default('available')->comment('状态');
            $table->decimal('average_rating', 3, 2)->default(0.00)->comment('平均评分');
            $table->unsignedInteger('review_count')->default(0)->comment('评价数量');
            $table->unsignedInteger('sales_count')->default(0)->comment('销量');
            $table->unsignedInteger('sort_order')->default(0)->comment('排序');
            $table->timestamps();

            $table->index('category_id');
            $table->index('status');
            $table->index('average_rating');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dishes');
    }
};

