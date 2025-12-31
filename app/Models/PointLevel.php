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

class PointLevel extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'min_points',
        'discount_type',
        'discount_value',
        'max_discount_amount',
        'min_order_amount',
        'is_active',
        'sort_order',
        'description',
        'icon',
        'color',
    ];

    protected $casts = [
        'min_points' => 'integer',
        'discount_value' => 'decimal:2',
        'max_discount_amount' => 'decimal:2',
        'min_order_amount' => 'decimal:2',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * 获取该段位的所有会员
     */
    public function memberPoints(): HasMany
    {
        return $this->hasMany(MemberPoint::class, 'level', 'code');
    }

    /**
     * 计算订单折扣
     *
     * @param float $orderAmount 订单金额
     * @return float 折扣金额
     */
    public function calculateDiscount(float $orderAmount): float
    {
        // 如果折扣类型为 none 或订单金额小于最低订单金额，返回 0
        if ($this->discount_type === 'none' || $orderAmount < $this->min_order_amount) {
            return 0;
        }

        $discount = 0;

        if ($this->discount_type === 'percentage') {
            // 百分比折扣：订单金额 * (折扣值 / 100)
            $discount = $orderAmount * ($this->discount_value / 100);
            
            // 如果有最大折扣金额限制，则不超过该限制
            if ($this->max_discount_amount !== null && $discount > $this->max_discount_amount) {
                $discount = $this->max_discount_amount;
            }
        } elseif ($this->discount_type === 'fixed') {
            // 固定金额折扣：直接使用折扣值，但不能超过订单金额
            $discount = min($this->discount_value, $orderAmount);
        }

        return round((float) $discount, 2);
    }

    /**
     * 获取所有启用的段位，按排序和积分要求排序
     */
    public static function getActiveLevels()
    {
        return static::where('is_active', true)
            ->orderBy('sort_order', 'asc')
            ->orderBy('min_points', 'asc')
            ->get();
    }

    /**
     * 根据积分获取对应的段位
     * 
     * 重要：段位判断基于总积分（total_points），而不是可用积分（available_points）
     * 总积分是用户累计获得的所有积分，不会因为积分兑换、过期而减少
     * 可用积分会因兑换、过期等原因减少，但不影响段位判断
     *
     * @param int $totalPoints 总积分（累计获得的所有积分）
     * @return PointLevel|null 对应的段位，如果没有匹配的段位则返回null
     */
    public static function getLevelByPoints(int $totalPoints): ?self
    {
        return static::where('is_active', true)
            ->where('min_points', '<=', $totalPoints)
            ->orderBy('min_points', 'desc')
            ->first();
    }
}

