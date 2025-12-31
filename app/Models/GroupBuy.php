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
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class GroupBuy extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'image_url',
        'original_price',
        'group_price',
        'stock',
        'sold_count',
        'start_time',
        'end_time',
        'valid_from',
        'valid_to',
        'valid_days',
        'limit_per_user',
        'is_active',
        'sort_order',
        'rules',
        'status',
    ];

    protected $casts = [
        'original_price' => 'decimal:2',
        'group_price' => 'decimal:2',
        'stock' => 'integer',
        'sold_count' => 'integer',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'valid_from' => 'datetime',
        'valid_to' => 'datetime',
        'valid_days' => 'integer',
        'limit_per_user' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'rules' => 'array',
    ];

    /**
     * 团购包含的菜品
     */
    public function items(): HasMany
    {
        return $this->hasMany(GroupBuyItem::class);
    }

    /**
     * 团购订单
     */
    public function orders(): HasMany
    {
        return $this->hasMany(GroupBuyOrder::class);
    }

    /**
     * 通过订单关联的用户
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'group_buy_orders')
            ->withPivot(['quantity', 'total_price', 'status', 'paid_at', 'used_at', 'expires_at'])
            ->withTimestamps();
    }

    /**
     * 检查团购是否可用
     */
    public function isAvailable(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->status !== 'published' && $this->status !== 'ongoing') {
            return false;
        }

        // 检查库存
        if ($this->stock > 0 && $this->sold_count >= $this->stock) {
            return false;
        }

        $now = now();
        
        // 检查团购时间
        if ($this->start_time && $now->lessThan($this->start_time)) {
            return false;
        }

        if ($this->end_time && $now->greaterThan($this->end_time)) {
            return false;
        }

        return true;
    }

    /**
     * 计算节省金额
     */
    public function getSavingsAttribute(): float
    {
        return max(0, (float) $this->original_price - (float) $this->group_price);
    }

    /**
     * 计算折扣百分比
     */
    public function getDiscountPercentAttribute(): float
    {
        if ($this->original_price <= 0) {
            return 0;
        }
        return round((($this->original_price - $this->group_price) / $this->original_price) * 100, 1);
    }

    /**
     * 获取剩余库存
     */
    public function getRemainingStockAttribute(): ?int
    {
        if ($this->stock <= 0) {
            return null; // 不限制
        }
        return max(0, $this->stock - $this->sold_count);
    }
}
