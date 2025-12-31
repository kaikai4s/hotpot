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
        Schema::create('queue', function (Blueprint $table) {
            $table->id();
            $table->string('queue_number', 20)->unique()->comment('排队号');
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict')->comment('用户ID');
            $table->unsignedInteger('guest_count')->comment('用餐人数');
            $table->string('table_type', 20)->nullable()->comment('桌位类型偏好');
            $table->unsignedInteger('position')->comment('当前位置');
            $table->enum('status', ['waiting', 'called', 'cancelled', 'seated'])->default('waiting')->comment('状态');
            $table->timestamp('joined_at')->useCurrent()->comment('加入时间');
            $table->timestamp('called_at')->nullable()->comment('叫号时间');
            $table->timestamp('seated_at')->nullable()->comment('入座时间');
            $table->timestamps();

            $table->index('user_id');
            $table->index('status');
            $table->index('position');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('queue');
    }
};

