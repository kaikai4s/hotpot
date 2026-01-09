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
        Schema::create('user_birthdays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->date('birthday')->comment('生日日期');
            $table->timestamp('last_modified_at')->nullable()->comment('上次修改时间');
            $table->unsignedSmallInteger('last_modified_year')->nullable()->comment('上次修改的年份');
            $table->timestamps();

            $table->index('birthday');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_birthdays');
    }
};
