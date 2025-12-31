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

class Coupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'value',
        'dish_id',
        'min_amount',
        'points_required',
        'stock',
        'valid_from',
        'valid_to',
        'is_active',
        'is_new_user_only',
        'description',
        'usage_instructions',
        'image_url',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'dish_id' => 'integer',
        'min_amount' => 'decimal:2',
        'points_required' => 'integer',
        'stock' => 'integer',
        'is_active' => 'boolean',
        'is_new_user_only' => 'boolean',
        'valid_from' => 'datetime',
        'valid_to' => 'datetime',
    ];

    public function userCoupons(): HasMany
    {
        return $this->hasMany(UserCoupon::class);
    }

    public function dish(): BelongsTo
    {
        return $this->belongsTo(Dish::class);
    }

    /**
     * 检查优惠券是否可用
     */
    public function isUsable(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->stock <= 0) {
            return false;
        }

        $now = now();
        if ($this->valid_from && $now->lessThan($this->valid_from)) {
            return false;
        }

        if ($this->valid_to && $now->greaterThan($this->valid_to)) {
            return false;
        }

        return true;
    }

    /**
     * 计算优惠金额
     */
    public function calculateDiscount(float $orderAmount): float
    {
        if (!$this->isUsable()) {
            return 0;
        }

        // 检查最低使用金额
        if ($this->min_amount > 0 && $orderAmount < $this->min_amount) {
            return 0;
        }

        switch ($this->type) {
            case 'fixed_amount':
                // 固定金额，但不能超过订单金额
                return min((float) $this->value, $orderAmount);
            
            case 'percentage':
                // 百分比折扣
                $discount = $orderAmount * ((float) $this->value / 100);
                // 如果有最大折扣限制，使用value作为最大值
                return min($discount, (float) $this->value);
            
            case 'dish_exchange':
                // 兑换菜品，返回菜品价格
                if ($this->dish) {
                    return (float) $this->dish->price;
                }
                return 0;
            
            default:
                return 0;
        }
    }
}

