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

class TaskTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'type',
        'category',
        'target_value',
        'reward_points',
        'reward_coupon_id',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'target_value' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'reward_points' => 'integer',
    ];

    public function rewardCoupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class, 'reward_coupon_id');
    }

    public function userTasks(): HasMany
    {
        return $this->hasMany(UserTask::class);
    }
}

