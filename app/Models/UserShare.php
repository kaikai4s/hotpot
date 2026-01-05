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

class UserShare extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'share_type',
        'share_content_id',
        'share_platform',
        'reward_points',
        'reward_issued',
    ];

    protected $casts = [
        'reward_points' => 'integer',
        'reward_issued' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

