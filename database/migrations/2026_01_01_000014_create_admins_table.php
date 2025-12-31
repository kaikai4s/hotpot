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
        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('username', 64)->unique()->comment('用户名');
            $table->string('email', 255)->unique()->nullable()->comment('邮箱');
            $table->string('password')->comment('密码（哈希）');
            $table->string('name', 64)->comment('姓名');
            $table->enum('role', ['super_admin', 'admin', 'operator'])->default('operator')->comment('角色');
            $table->boolean('is_active')->default(true)->comment('是否启用');
            $table->timestamp('last_login_at')->nullable()->comment('最后登录时间');
            $table->timestamps();

            $table->index('role');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admins');
    }
};

