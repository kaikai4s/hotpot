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

class UserCheckinStat extends Model
{
    use HasFactory;

    protected $table = 'user_checkin_stats';

    protected $fillable = [
        'user_id',
        'total_days',
        'max_consecutive_days',
        'current_consecutive_days',
        'last_checkin_date',
        'makeup_count',
    ];

    protected $casts = [
        'total_days' => 'integer',
        'max_consecutive_days' => 'integer',
        'current_consecutive_days' => 'integer',
        'last_checkin_date' => 'date',
        'makeup_count' => 'integer',
    ];

    public $timestamps = false;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

