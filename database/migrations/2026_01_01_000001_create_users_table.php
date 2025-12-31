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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('openid', 64)->unique()->comment('微信OpenID');
            $table->string('unionid', 64)->nullable()->index()->comment('微信UnionID');
            $table->string('nickname', 64)->nullable()->comment('昵称');
            $table->string('avatar_url', 255)->nullable()->comment('头像URL');
            $table->string('phone', 20)->nullable()->comment('手机号');
            $table->tinyInteger('gender')->nullable()->comment('性别：0未知，1男，2女');
            $table->timestamps();

            $table->index('openid');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};

