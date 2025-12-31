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
        Schema::table('orders', function (Blueprint $table) {
            $table->enum('payment_method', ['wechat', 'mock'])->nullable()->after('status')->comment('支付方式');
            $table->string('payment_transaction_id')->nullable()->after('payment_method')->comment('支付交易号');
            $table->json('payment_data')->nullable()->after('payment_transaction_id')->comment('支付数据（JSON）');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['payment_method', 'payment_transaction_id', 'payment_data']);
        });
    }
};
