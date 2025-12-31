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
        Schema::create('admin_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('admins')->onDelete('cascade')->comment('管理员ID');
            $table->foreignId('role_id')->constrained('roles')->onDelete('cascade')->comment('角色ID');
            $table->timestamps();

            $table->unique(['admin_id', 'role_id']);
            $table->index('admin_id');
            $table->index('role_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_roles');
    }
};
