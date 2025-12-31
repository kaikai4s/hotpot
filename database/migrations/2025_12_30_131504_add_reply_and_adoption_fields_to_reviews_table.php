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
        Schema::table('reviews', function (Blueprint $table) {
            // 管理员回复相关字段
            $table->text('admin_reply')->nullable()->after('reviewed_at')->comment('管理员回复内容');
            $table->timestamp('admin_replied_at')->nullable()->after('admin_reply')->comment('管理员回复时间');
            $table->foreignId('admin_replied_by')->nullable()->after('admin_replied_at')->constrained('users')->onDelete('set null')->comment('回复的管理员ID');
            
            // 采纳相关字段
            $table->boolean('is_adopted')->default(false)->after('admin_replied_by')->comment('是否被采纳');
            $table->timestamp('adopted_at')->nullable()->after('is_adopted')->comment('采纳时间');
            $table->foreignId('adopted_by')->nullable()->after('adopted_at')->constrained('users')->onDelete('set null')->comment('采纳的管理员ID');
            
            // 追踪优化相关字段
            $table->enum('tracking_status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending')->after('adopted_by')->comment('追踪状态');
            $table->json('tracking_updates')->nullable()->after('tracking_status')->comment('追踪更新记录');
            
            // 添加索引
            $table->index('is_adopted');
            $table->index('tracking_status');
            $table->index('admin_replied_by');
            $table->index('adopted_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropForeign(['admin_replied_by']);
            $table->dropForeign(['adopted_by']);
            $table->dropIndex(['is_adopted']);
            $table->dropIndex(['tracking_status']);
            $table->dropIndex(['admin_replied_by']);
            $table->dropIndex(['adopted_by']);
            
            $table->dropColumn([
                'admin_reply',
                'admin_replied_at',
                'admin_replied_by',
                'is_adopted',
                'adopted_at',
                'adopted_by',
                'tracking_status',
                'tracking_updates',
            ]);
        });
    }
};
