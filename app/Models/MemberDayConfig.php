<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MemberDayConfig extends Model
{
    protected $fillable = [
        'day_of_month',
        'is_enabled',
        'base_discount',
        'points_bonus_rate',
        'discount_by_level',
        'current_month_override',
    ];

    protected $casts = [
        'day_of_month' => 'integer',
        'is_enabled' => 'boolean',
        'base_discount' => 'decimal:2',
        'points_bonus_rate' => 'decimal:2',
        'discount_by_level' => 'array',
        'current_month_override' => 'integer',
    ];

    public const LEVEL_BRONZE = 'bronze';
    public const LEVEL_SILVER = 'silver';
    public const LEVEL_GOLD = 'gold';
    public const LEVEL_PLATINUM = 'platinum';

    public function getDiscountForLevel(string $level): float
    {
        $discounts = $this->discount_by_level ?? [];
        return (float) ($discounts[$level] ?? $this->base_discount);
    }
}
