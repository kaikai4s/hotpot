<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;

class MemberPrivilegeService
{
    private BirthdayPrivilegeService $birthdayService;
    private MemberDayService $memberDayService;

    /**
     * 会员等级权益配置
     */
    private const LEVEL_PRIVILEGES = [
        'bronze' => [
            'name' => '青铜会员',
            'points_multiplier' => 1.0,
            'birthday_coupon' => 20,
            'member_day_discount' => 0.90,
            'free_shipping_threshold' => 200,
        ],
        'silver' => [
            'name' => '白银会员',
            'points_multiplier' => 1.2,
            'birthday_coupon' => 30,
            'member_day_discount' => 0.88,
            'free_shipping_threshold' => 150,
        ],
        'gold' => [
            'name' => '黄金会员',
            'points_multiplier' => 1.5,
            'birthday_coupon' => 50,
            'member_day_discount' => 0.85,
            'free_shipping_threshold' => 100,
        ],
        'platinum' => [
            'name' => '白金会员',
            'points_multiplier' => 2.0,
            'birthday_coupon' => 100,
            'member_day_discount' => 0.80,
            'free_shipping_threshold' => 0,
        ],
    ];

    public function __construct(
        BirthdayPrivilegeService $birthdayService,
        MemberDayService $memberDayService
    ) {
        $this->birthdayService = $birthdayService;
        $this->memberDayService = $memberDayService;
    }

    /**
     * 获取用户权益概览
     */
    public function getPrivilegeOverview(User $user): array
    {
        $level = $user->member_level ?? 'bronze';
        $levelPrivileges = $this->getLevelPrivileges($level);

        return [
            'user_id' => $user->id,
            'level' => $level,
            'level_name' => $levelPrivileges['name'],
            'privileges' => $levelPrivileges,
            'birthday' => [
                'is_birthday' => $this->birthdayService->isBirthday($user),
                'has_coupon_this_year' => $this->birthdayService->hasBirthdayCouponThisYear($user),
                'has_dessert_this_year' => $this->birthdayService->hasBirthdayDessertThisYear($user),
            ],
            'member_day' => [
                'is_member_day' => $this->memberDayService->isMemberDay(),
                'days_until' => $this->memberDayService->getDaysUntilMemberDay(),
                'next_date' => $this->memberDayService->getNextMemberDay()->format('Y-m-d'),
            ],
            'next_level' => $this->getNextLevelPrivileges($level),
        ];
    }

    /**
     * 获取指定等级的权益
     */
    public function getLevelPrivileges(string $level): array
    {
        return self::LEVEL_PRIVILEGES[$level] ?? self::LEVEL_PRIVILEGES['bronze'];
    }

    /**
     * 获取下一等级的权益
     */
    public function getNextLevelPrivileges(string $currentLevel): ?array
    {
        $levels = array_keys(self::LEVEL_PRIVILEGES);
        $currentIndex = array_search($currentLevel, $levels);

        if ($currentIndex === false || $currentIndex >= count($levels) - 1) {
            return null; // 已是最高等级
        }

        $nextLevel = $levels[$currentIndex + 1];
        return [
            'level' => $nextLevel,
            'privileges' => self::LEVEL_PRIVILEGES[$nextLevel],
        ];
    }

    /**
     * 计算最终积分倍数
     * Property 10: 生日与会员日积分叠加
     * 
     * 生日双倍 + 会员日50%加成 = 2.5倍
     */
    public function calculateFinalPointsMultiplier(User $user, ?Carbon $date = null): float
    {
        $date = $date ?? Carbon::today();
        $level = $user->member_level ?? 'bronze';
        $levelPrivileges = $this->getLevelPrivileges($level);
        
        $baseMultiplier = $levelPrivileges['points_multiplier'];
        
        $isBirthday = $this->birthdayService->isBirthday($user, $date);
        $isMemberDay = $this->memberDayService->isMemberDay($date);

        if ($isBirthday && $isMemberDay) {
            // 生日 + 会员日 = 2.5倍
            return $baseMultiplier * 2.5;
        } elseif ($isBirthday) {
            // 生日双倍
            return $baseMultiplier * 2.0;
        } elseif ($isMemberDay) {
            // 会员日 1.5倍
            return $baseMultiplier * 1.5;
        }

        return $baseMultiplier;
    }

    /**
     * 计算最终折扣
     */
    public function calculateFinalDiscount(User $user, float $amount, ?Carbon $date = null): float
    {
        $date = $date ?? Carbon::today();
        $level = $user->member_level ?? 'bronze';

        if (!$this->memberDayService->isMemberDay($date)) {
            return $amount; // 非会员日，不打折
        }

        $discount = $this->memberDayService->getMemberDayDiscount($level);
        return $amount * $discount;
    }

    /**
     * 获取用户权益统计
     * Property 16: 权益统计准确性
     */
    public function getPrivilegeStats(User $user): array
    {
        return [
            'saved_amount' => $this->getSavedAmount($user),
            'earned_bonus_points' => $this->getEarnedBonusPoints($user),
            'redeemed_products' => $this->getRedeemedProductsCount($user),
            'birthday_privileges_used' => $this->getBirthdayPrivilegesUsed($user),
        ];
    }

    /**
     * 获取用户节省金额
     */
    public function getSavedAmount(User $user): float
    {
        // 从订单记录中统计会员日折扣节省的金额
        // 这里返回模拟值，实际需要从订单表查询
        return 0.0;
    }

    /**
     * 获取用户获得的额外积分
     */
    public function getEarnedBonusPoints(User $user): int
    {
        // 从积分流水中统计生日和会员日获得的额外积分
        // 这里返回模拟值，实际需要从积分流水表查询
        return 0;
    }

    /**
     * 获取用户兑换商品数量
     */
    private function getRedeemedProductsCount(User $user): int
    {
        return $user->productRedemptions()->count();
    }

    /**
     * 获取用户已使用的生日特权
     */
    private function getBirthdayPrivilegesUsed(User $user): array
    {
        $currentYear = Carbon::now()->year;
        
        return [
            'coupon' => $this->birthdayService->hasBirthdayCouponThisYear($user),
            'dessert' => $this->birthdayService->hasBirthdayDessertThisYear($user),
        ];
    }
}
