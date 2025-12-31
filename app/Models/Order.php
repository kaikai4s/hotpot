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
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_no',
        'user_id',
        'table_id',
        'reservation_id',
        'total_amount',
        'deposit_discount',
        'points_discount',
        'points_used',
        'user_coupon_id',
        'coupon_discount',
        'user_level_code',
        'level_discount',
        'final_amount',
        'status',
        'payment_method',
        'payment_transaction_id',
        'payment_data',
        'paid_at',
        'completed_at',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'deposit_discount' => 'decimal:2',
        'points_discount' => 'decimal:2',
        'points_used' => 'integer',
        'user_coupon_id' => 'integer',
        'coupon_discount' => 'decimal:2',
        'level_discount' => 'decimal:2',
        'final_amount' => 'decimal:2',
        'payment_data' => 'array',
        'paid_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function table(): BelongsTo
    {
        return $this->belongsTo(Table::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function userCoupon(): BelongsTo
    {
        return $this->belongsTo(UserCoupon::class);
    }

    /**
     * 获取下单时的段位信息
     */
    public function userLevel(): BelongsTo
    {
        return $this->belongsTo(PointLevel::class, 'user_level_code', 'code');
    }
}

