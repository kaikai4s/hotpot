<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BirthdayDessertVoucher extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'year',
        'code',
        'status',
        'expires_at',
        'used_at',
        'order_id',
        'created_at',
    ];

    protected $casts = [
        'year' => 'integer',
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
        'order_id' => 'integer',
        'created_at' => 'datetime',
    ];

    public const STATUS_UNUSED = 'unused';
    public const STATUS_USED = 'used';
    public const STATUS_EXPIRED = 'expired';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function isValid(): bool
    {
        return $this->status === self::STATUS_UNUSED && $this->expires_at->isFuture();
    }
}
