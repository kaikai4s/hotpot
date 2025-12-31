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
        // 先将 enum 值转换为字符串
        DB::statement("ALTER TABLE member_points MODIFY COLUMN level VARCHAR(50) NOT NULL DEFAULT 'bronze' COMMENT '等级（段位代码）'");
        
        // 更新现有数据，确保使用段位代码
        DB::statement("UPDATE member_points SET level = 'bronze' WHERE level = 'bronze'");
        DB::statement("UPDATE member_points SET level = 'silver' WHERE level = 'silver'");
        DB::statement("UPDATE member_points SET level = 'gold' WHERE level = 'gold'");
        DB::statement("UPDATE member_points SET level = 'platinum' WHERE level = 'platinum'");
    }

    public function down(): void
    {
        // 恢复为 enum 类型
        DB::statement("ALTER TABLE member_points MODIFY COLUMN level ENUM('bronze', 'silver', 'gold', 'platinum') NOT NULL DEFAULT 'bronze' COMMENT '等级'");
    }
};

