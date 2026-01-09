<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory;

    protected $fillable = [
        'openid',
        'unionid',
        'nickname',
        'avatar_url',
        'phone',
        'password',
        'gender',
        'is_active',
        'remark',
        'invite_code',
        'invited_by',
        'equipped_title',
    ];

    protected $hidden = [
        'password',
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

    public function invitations(): HasMany
    {
        return $this->hasMany(UserInvitation::class, 'inviter_id');
    }

    public function invitation(): HasOne
    {
        return $this->hasOne(UserInvitation::class, 'invitee_id');
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function invitees(): HasMany
    {
        return $this->hasMany(User::class, 'invited_by');
    }

    public function userTasks(): HasMany
    {
        return $this->hasMany(UserTask::class);
    }

    public function checkins(): HasMany
    {
        return $this->hasMany(UserCheckin::class);
    }

    public function checkinStat(): HasOne
    {
        return $this->hasOne(UserCheckinStat::class);
    }

    public function achievements(): HasMany
    {
        return $this->hasMany(UserAchievement::class);
    }

    public function shares(): HasMany
    {
        return $this->hasMany(UserShare::class);
    }

    public function birthday(): HasOne
    {
        return $this->hasOne(UserBirthday::class);
    }

    public function birthdayPrivilegeLogs(): HasMany
    {
        return $this->hasMany(BirthdayPrivilegeLog::class);
    }

    public function birthdayDessertVouchers(): HasMany
    {
        return $this->hasMany(BirthdayDessertVoucher::class);
    }

    public function productRedemptions(): HasMany
    {
        return $this->hasMany(ProductRedemption::class);
    }
}

