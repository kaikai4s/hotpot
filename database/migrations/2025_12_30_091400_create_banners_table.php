<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

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
        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->string('title')->comment('标题');
            $table->text('description')->nullable()->comment('描述');
            $table->string('image_url')->comment('图片URL');
            $table->string('link_url')->nullable()->comment('链接URL');
            $table->string('link_type')->default('none')->comment('链接类型：none, internal, external');
            $table->integer('sort_order')->default(0)->comment('排序');
            $table->boolean('is_active')->default(true)->comment('是否启用');
            $table->timestamp('start_at')->nullable()->comment('开始时间');
            $table->timestamp('end_at')->nullable()->comment('结束时间');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('banners');
    }
};
