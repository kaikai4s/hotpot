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

class PointExpiration extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'transaction_id',
        'points',
        'expire_at',
        'expired_at',
        'status',
    ];

    protected $casts = [
        'points' => 'integer',
        'expire_at' => 'datetime',
        'expired_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(PointTransaction::class, 'transaction_id');
    }

    /**
     * 获取待过期的记录
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * 获取即将过期的记录
     */
    public function scopeExpiring($query, int $days = 30)
    {
        return $query->where('status', 'pending')
            ->whereBetween('expire_at', [now(), now()->addDays($days)]);
    }

    /**
     * 获取已过期的记录
     */
    public function scopeExpired($query)
    {
        return $query->where('status', 'expired');
    }
}

