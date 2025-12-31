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
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 修改订单状态枚举，添加 pending_review 状态
        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('pending', 'paid', 'pending_review', 'completed', 'cancelled') DEFAULT 'pending' COMMENT '订单状态'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 将 pending_review 状态的订单改为 completed
        DB::statement("UPDATE orders SET status = 'completed' WHERE status = 'pending_review'");
        
        // 恢复原来的枚举
        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('pending', 'paid', 'completed', 'cancelled') DEFAULT 'pending' COMMENT '订单状态'");
    }
};
