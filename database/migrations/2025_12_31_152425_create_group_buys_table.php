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
        Schema::create('group_buys', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->comment('团购名称');
            $table->text('description')->nullable()->comment('团购描述');
            $table->string('image_url', 255)->nullable()->comment('团购图片');
            $table->decimal('original_price', 10, 2)->comment('原价');
            $table->decimal('group_price', 10, 2)->comment('团购价');
            $table->unsignedInteger('min_people')->default(1)->comment('最少成团人数');
            $table->unsignedInteger('max_people')->nullable()->comment('最多参团人数（null表示不限制）');
            $table->unsignedInteger('stock')->default(0)->comment('库存（0表示不限制）');
            $table->unsignedInteger('sold_count')->default(0)->comment('已售数量');
            $table->timestamp('start_time')->nullable()->comment('团购开始时间');
            $table->timestamp('end_time')->nullable()->comment('团购结束时间');
            $table->timestamp('valid_from')->nullable()->comment('有效期开始（购买后可使用的时间）');
            $table->timestamp('valid_to')->nullable()->comment('有效期结束');
            $table->unsignedInteger('valid_days')->default(0)->comment('有效期天数（购买后N天内有效，0表示不限制）');
            $table->unsignedInteger('limit_per_user')->default(0)->comment('每人限购数量（0表示不限制）');
            $table->boolean('is_active')->default(true)->comment('是否启用');
            $table->integer('sort_order')->default(0)->comment('排序（数字越小越靠前）');
            $table->json('rules')->nullable()->comment('自定义规则（JSON格式）');
            $table->enum('status', ['draft', 'published', 'ongoing', 'ended', 'cancelled'])->default('draft')->comment('状态：draft草稿, published已发布, ongoing进行中, ended已结束, cancelled已取消');
            $table->timestamps();

            $table->index('status');
            $table->index('is_active');
            $table->index('sort_order');
            $table->index(['start_time', 'end_time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('group_buys');
    }
};
