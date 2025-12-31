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
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->string('reservation_code', 32)->unique()->comment('预约编码');
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict')->comment('用户ID');
            $table->foreignId('table_id')->constrained('tables')->onDelete('restrict')->comment('桌位ID');
            $table->date('date')->comment('预约日期');
            $table->time('time_slot')->comment('时间段');
            $table->unsignedInteger('guest_count')->comment('用餐人数');
            $table->string('contact_name', 64)->comment('联系人姓名');
            $table->string('contact_phone', 20)->comment('联系电话');
            $table->text('special_requests')->nullable()->comment('特殊需求');
            $table->enum('status', ['pending', 'confirmed', 'cancelled', 'completed', 'expired'])->default('pending')->comment('状态');
            $table->string('idempotency_key', 64)->nullable()->unique()->comment('幂等性密钥');
            $table->timestamp('expires_at')->nullable()->comment('过期时间（pending状态）');
            $table->timestamp('confirmed_at')->nullable()->comment('确认时间');
            $table->timestamp('cancelled_at')->nullable()->comment('取消时间');
            $table->timestamps();

            $table->index('user_id');
            $table->index('table_id');
            $table->index(['date', 'time_slot']);
            $table->index('status');
            $table->index('idempotency_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};

