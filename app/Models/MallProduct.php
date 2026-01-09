<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MallProduct extends Model
{
    protected $fillable = [
        'name',
        'description',
        'image_url',
        'type',
        'points_required',
        'stock',
        'per_user_limit',
        'status',
        'valid_days',
        'experience_config',
        'sort_order',
    ];

    protected $casts = [
        'points_required' => 'integer',
        'stock' => 'integer',
        'per_user_limit' => 'integer',
        'valid_days' => 'integer',
        'experience_config' => 'array',
        'sort_order' => 'integer',
    ];

    public const TYPE_PHYSICAL = 'physical';
    public const TYPE_EXPERIENCE = 'experience';
    public const TYPE_COUPON = 'coupon';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_SOLD_OUT = 'sold_out';

    public function redemptions(): HasMany
    {
        return $this->hasMany(ProductRedemption::class, 'product_id');
    }

    public function isPhysical(): bool
    {
        return $this->type === self::TYPE_PHYSICAL;
    }

    public function isExperience(): bool
    {
        return $this->type === self::TYPE_EXPERIENCE;
    }

    public function isAvailable(): bool
    {
        return $this->status === self::STATUS_ACTIVE && $this->stock > 0;
    }
}
