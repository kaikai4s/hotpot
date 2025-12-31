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

class LotteryActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'image_url',
        'start_time',
        'end_time',
        'daily_limit',
        'total_limit',
        'points_cost',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'daily_limit' => 'integer',
        'total_limit' => 'integer',
        'points_cost' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function prizes(): HasMany
    {
        return $this->hasMany(LotteryPrize::class)->where('is_active', true)->orderBy('sort_order');
    }

    public function records(): HasMany
    {
        return $this->hasMany(LotteryRecord::class);
    }

    /**
     * 检查活动是否进行中
     */
    public function isActive(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = now();
        return $now->greaterThanOrEqualTo($this->start_time) && $now->lessThanOrEqualTo($this->end_time);
    }
}

