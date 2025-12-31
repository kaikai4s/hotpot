<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            // 添加新用户专享字段
            $table->boolean('is_new_user_only')->default(false)->after('is_active')->comment('是否仅限新用户');
            
            // 修改type枚举，添加新类型
            // 注意：MySQL不支持直接修改ENUM，需要先删除再添加
        });

        // 修改type枚举值（MySQL需要特殊处理）
        DB::statement("ALTER TABLE coupons MODIFY COLUMN type ENUM('discount', 'cash', 'points', 'dish_exchange', 'fixed_amount', 'percentage', 'new_user') NOT NULL COMMENT '类型'");
    }

    public function down(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->dropColumn('is_new_user_only');
        });

        // 恢复原来的枚举值
        DB::statement("ALTER TABLE coupons MODIFY COLUMN type ENUM('discount', 'cash', 'points') NOT NULL COMMENT '类型'");
    }
};
