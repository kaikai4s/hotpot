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
use App\Models\Admin;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'order_id',
        'dish_id',
        'rating',
        'content',
        'images',
        'tags',
        'status',
        'helpful_count',
        'reviewed_at',
        'admin_reply',
        'admin_replied_at',
        'admin_replied_by',
        'is_adopted',
        'adopted_at',
        'adopted_by',
        'tracking_status',
        'tracking_updates',
    ];

    protected $casts = [
        'rating' => 'integer',
        'images' => 'array',
        'tags' => 'array',
        'helpful_count' => 'integer',
        'reviewed_at' => 'datetime',
        'admin_replied_at' => 'datetime',
        'is_adopted' => 'boolean',
        'adopted_at' => 'datetime',
        'tracking_updates' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function dish(): BelongsTo
    {
        return $this->belongsTo(Dish::class);
    }

    public function adminReplier(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'admin_replied_by');
    }

    public function adopter(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'adopted_by');
    }
}

