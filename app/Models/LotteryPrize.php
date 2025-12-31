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
use Illuminate\Database\Eloquent\Relations\HasMany;

class LotteryPrize extends Model
{
    use HasFactory;

    protected $fillable = [
        'lottery_activity_id',
        'name',
        'description',
        'image_url',
        'prize_type',
        'prize_id',
        'prize_value',
        'probability',
        'stock',
        'daily_stock',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'prize_id' => 'integer',
        'prize_value' => 'integer',
        'probability' => 'integer',
        'stock' => 'integer',
        'daily_stock' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function activity(): BelongsTo
    {
        return $this->belongsTo(LotteryActivity::class);
    }

    public function records(): HasMany
    {
        return $this->hasMany(LotteryRecord::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class, 'prize_id');
    }

    public function dish(): BelongsTo
    {
        return $this->belongsTo(Dish::class, 'prize_id');
    }
}

