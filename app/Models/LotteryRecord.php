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

class LotteryRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'lottery_activity_id',
        'lottery_prize_id',
        'prize_type',
        'prize_id',
        'prize_value',
        'is_winner',
    ];

    protected $casts = [
        'prize_id' => 'integer',
        'prize_value' => 'integer',
        'is_winner' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(LotteryActivity::class);
    }

    public function prize(): BelongsTo
    {
        return $this->belongsTo(LotteryPrize::class, 'lottery_prize_id');
    }
}

