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

class PointTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'points',
        'balance_after',
        'source_type',
        'source_id',
        'description',
        'expire_at',
    ];

    protected $casts = [
        'points' => 'integer',
        'balance_after' => 'integer',
        'source_id' => 'integer',
        'expire_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

