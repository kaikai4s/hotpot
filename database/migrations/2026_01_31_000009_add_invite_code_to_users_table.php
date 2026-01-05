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
        Schema::table('users', function (Blueprint $table) {
            $table->string('invite_code', 32)->nullable()->unique()->after('phone')->comment('邀请码（格式：INV{user_id}{6位随机}）');
            $table->unsignedBigInteger('invited_by')->nullable()->after('invite_code')->comment('邀请人ID（外键users）');
        });

        // 添加外键约束
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('invited_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['invited_by']);
            $table->dropColumn(['invite_code', 'invited_by']);
        });
    }
};

