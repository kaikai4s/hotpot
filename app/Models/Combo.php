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

class Combo extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'image_url',
        'price',
        'stock',
        'sold_count',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'stock' => 'integer',
        'sold_count' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * 套餐包含的菜品
     */
    public function dishes(): HasMany
    {
        return $this->hasMany(ComboDish::class);
    }

    /**
     * 订单项
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'combo_id');
    }

    /**
     * 计算原价总计（所有菜品原价总和）
     */
    public function getOriginalTotalAttribute(): float
    {
        $total = 0;
        foreach ($this->dishes as $comboDish) {
            $total += $comboDish->dish->price * $comboDish->quantity;
        }
        return (float) $total;
    }

    /**
     * 计算优惠金额
     */
    public function getSavingsAttribute(): float
    {
        return max(0, (float) $this->original_total - (float) $this->price);
    }

    /**
     * 计算折扣百分比
     */
    public function getDiscountPercentAttribute(): float
    {
        if ($this->original_total <= 0) {
            return 0;
        }
        return round((($this->original_total - $this->price) / $this->original_total) * 100, 1);
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

    /**
     * 检查套餐是否可用
     */
    public function isAvailable(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        // 检查库存
        if ($this->stock > 0 && $this->sold_count >= $this->stock) {
            return false;
        }

        // 检查套餐中的菜品是否都可用
        foreach ($this->dishes as $comboDish) {
            if ($comboDish->dish->status !== 'available') {
                return false;
            }
        }

        return true;
    }
}

