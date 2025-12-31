<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Laravel\Sanctum\HasApiTokens;

class User extends Model
{
    use HasApiTokens, HasFactory;

    protected $fillable = [
        'openid',
        'unionid',
        'nickname',
        'avatar_url',
        'phone',
        'gender',
        'is_active',
        'remark',
    ];

    protected $casts = [
        'gender' => 'integer',
        'is_active' => 'boolean',
    ];

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function queue(): HasMany
    {
        return $this->hasMany(Queue::class);
    }

    public function memberPoints(): HasOne
    {
        return $this->hasOne(MemberPoint::class);
    }

    public function pointTransactions(): HasMany
    {
        return $this->hasMany(PointTransaction::class);
    }

    public function userCoupons(): HasMany
    {
        return $this->hasMany(UserCoupon::class);
    }
}

