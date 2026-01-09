<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductRedemption extends Model
{
    protected $fillable = [
        'user_id',
        'product_id',
        'points_used',
        'status',
        'shipping_address',
        'tracking_number',
        'experience_datetime',
        'experience_status',
        'notes',
        'completed_at',
    ];

    protected $casts = [
        'points_used' => 'integer',
        'shipping_address' => 'array',
        'experience_datetime' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_SHIPPED = 'shipped';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    public const EXPERIENCE_PENDING = 'pending';
    public const EXPERIENCE_USED = 'used';
    public const EXPERIENCE_EXPIRED = 'expired';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(MallProduct::class, 'product_id');
    }
}
