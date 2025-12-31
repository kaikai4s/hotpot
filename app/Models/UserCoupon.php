<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserCoupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'coupon_id',
        'code',
        'status',
        'used_at',
        'expires_at',
        'obtained_from',
        'obtained_at',
        'order_id',
    ];

    protected $casts = [
        'used_at' => 'datetime',
        'expires_at' => 'datetime',
        'obtained_at' => 'datetime',
        'order_id' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }
}

