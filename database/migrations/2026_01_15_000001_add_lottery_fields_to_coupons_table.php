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
        Schema::table('coupons', function (Blueprint $table) {
            // 扩展优惠券类型：支持兑换菜品、固定金额、折扣
            $table->enum('type', ['discount', 'cash', 'points', 'dish_exchange', 'fixed_amount', 'percentage'])
                ->default('fixed_amount')
                ->change()
                ->comment('类型：discount折扣(已废弃), cash现金(已废弃), points积分, dish_exchange兑换菜品, fixed_amount固定金额, percentage百分比折扣');
            
            // 添加菜品ID（用于兑换菜品类型）
            $table->unsignedBigInteger('dish_id')->nullable()->after('value')->comment('兑换的菜品ID（仅dish_exchange类型）');
            
            // 添加最小使用金额限制
            $table->decimal('min_amount', 10, 2)->default(0)->after('value')->comment('最低使用金额');
            
            // 添加描述
            $table->text('description')->nullable()->after('name')->comment('优惠券描述');
            
            // 添加使用说明
            $table->text('usage_instructions')->nullable()->after('description')->comment('使用说明');
            
            // 添加图片
            $table->string('image_url')->nullable()->after('usage_instructions')->comment('优惠券图片');
            
            // 添加外键
            $table->foreign('dish_id')->references('id')->on('dishes')->onDelete('set null');
            
            $table->index('dish_id');
        });
    }

    public function down(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->dropForeign(['dish_id']);
            $table->dropIndex(['dish_id']);
            $table->dropColumn(['dish_id', 'min_amount', 'description', 'usage_instructions', 'image_url']);
            $table->enum('type', ['discount', 'cash', 'points'])
                ->default('cash')
                ->change();
        });
    }
};

