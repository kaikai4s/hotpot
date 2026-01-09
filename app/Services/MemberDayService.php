<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Services;

use App\Models\MemberDayConfig;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class MemberDayService
{
    private const CACHE_KEY = 'member_day_config';
    private const CACHE_TTL = 3600; // 1 hour

    /**
     * 各会员等级默认折扣
     */
    private const DEFAULT_DISCOUNTS = [
        'bronze' => 0.90,
        'silver' => 0.88,
        'gold' => 0.85,
        'platinum' => 0.80,
    ];

    /**
     * 获取会员日配置
     */
    public function getConfig(): MemberDayConfig
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return MemberDayConfig::first() ?? $this->createDefaultConfig();
        });
    }

    /**
     * 更新会员日配置
     */
    public function updateConfig(array $data): MemberDayConfig
    {
        $config = MemberDayConfig::first() ?? new MemberDayConfig();
        
        $config->fill($data);
        $config->save();

        Cache::forget(self::CACHE_KEY);

        return $config;
    }

    /**
     * 检查会员日功能是否启用
     */
    public function isEnabled(): bool
    {
        return $this->getConfig()->is_enabled;
    }

    /**
     * 创建默认配置
     */
    private function createDefaultConfig(): MemberDayConfig
    {
        return MemberDayConfig::create([
            'day_of_month' => 8,
            'is_enabled' => true,
            'base_discount' => 0.88,
            'points_bonus_rate' => 0.50,
            'discount_by_level' => self::DEFAULT_DISCOUNTS,
        ]);
    }

    /**
     * 判断指定日期是否为会员日
     */
    public function isMemberDay(?Carbon $date = null): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        $checkDate = $date ?? Carbon::today();
        $config = $this->getConfig();

        // 检查当月是否有临时调整
        $memberDayOfMonth = $config->current_month_override ?? $config->day_of_month;

        return $checkDate->day === $memberDayOfMonth;
    }

    /**
     * 获取下一个会员日日期
     */
    public function getNextMemberDay(): Carbon
    {
        $config = $this->getConfig();
        $today = Carbon::today();
        
        // 获取当月会员日
        $memberDayOfMonth = $config->current_month_override ?? $config->day_of_month;
        $thisMonthMemberDay = Carbon::create($today->year, $today->month, $memberDayOfMonth);

        // 如果当月会员日还没过，返回当月会员日
        if ($thisMonthMemberDay->gte($today)) {
            return $thisMonthMemberDay;
        }

        // 否则返回下个月会员日（下个月不使用临时调整）
        return Carbon::create($today->year, $today->month, $config->day_of_month)->addMonth();
    }

    /**
     * 获取距离下次会员日的天数
     */
    public function getDaysUntilMemberDay(): int
    {
        $today = Carbon::today();
        $nextMemberDay = $this->getNextMemberDay();

        // 如果今天就是会员日，返回0
        if ($this->isMemberDay($today)) {
            return 0;
        }

        return $today->diffInDays($nextMemberDay);
    }

    /**
     * 设置当月临时会员日日期
     */
    public function setCurrentMonthOverride(?int $day): MemberDayConfig
    {
        $config = $this->getConfig();
        $config->current_month_override = $day;
        $config->save();

        Cache::forget(self::CACHE_KEY);

        return $config;
    }

    /**
     * 清除当月临时调整（每月初自动调用）
     */
    public function clearCurrentMonthOverride(): void
    {
        $config = MemberDayConfig::first();
        if ($config && $config->current_month_override !== null) {
            $config->current_month_override = null;
            $config->save();
            Cache::forget(self::CACHE_KEY);
        }
    }

    /**
     * 获取指定会员等级的会员日折扣
     */
    public function getMemberDayDiscount(string $level): float
    {
        $config = $this->getConfig();
        $discounts = $config->discount_by_level ?? self::DEFAULT_DISCOUNTS;

        return (float) ($discounts[$level] ?? $config->base_discount);
    }

    /**
     * 计算会员日折扣金额
     */
    public function calculateMemberDayDiscountAmount(float $amount, string $level): float
    {
        $discount = $this->getMemberDayDiscount($level);
        return $amount * (1 - $discount);
    }

    /**
     * 计算会员日折扣后的金额
     */
    public function calculateDiscountedAmount(float $amount, string $level): float
    {
        $discount = $this->getMemberDayDiscount($level);
        return $amount * $discount;
    }

    /**
     * 选择最优折扣（会员日折扣与其他折扣比较）
     */
    public function getBestDiscount(float $memberDayDiscount, float $otherDiscount): float
    {
        // 折扣值越小，优惠越大
        return min($memberDayDiscount, $otherDiscount);
    }

    /**
     * 获取会员日积分加成比例
     * 
     * @return float 返回0.5表示50%加成
     */
    public function getMemberDayPointsBonus(): float
    {
        $config = $this->getConfig();
        return (float) $config->points_bonus_rate;
    }

    /**
     * 计算会员日积分（含加成）
     * 
     * @param int $basePoints 基础积分
     * @return int 加成后的总积分
     */
    public function calculateMemberDayPoints(int $basePoints): int
    {
        $bonusRate = $this->getMemberDayPointsBonus();
        return (int) floor($basePoints * (1 + $bonusRate));
    }
}
