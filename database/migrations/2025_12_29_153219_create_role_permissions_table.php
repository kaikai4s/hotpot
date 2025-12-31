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
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained('roles')->onDelete('cascade')->comment('角色ID');
            $table->foreignId('permission_id')->constrained('permissions')->onDelete('cascade')->comment('权限ID');
            $table->timestamps();

            $table->unique(['role_id', 'permission_id']);
            $table->index('role_id');
            $table->index('permission_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_permissions');
    }
};
