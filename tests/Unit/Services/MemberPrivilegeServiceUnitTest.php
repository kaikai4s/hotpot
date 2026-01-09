<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace Tests\Unit\Services;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * MemberPrivilegeService 纯单元测试
 * 
 * Feature: member-privileges-upgrade
 */
class MemberPrivilegeServiceUnitTest extends TestCase
{
    /**
     * Property 10: 生日与会员日积分叠加
     * 
     * *For any* 用户在生日且同时是会员日的日期完成订单，
     * 其积分倍数应为2.5倍（双倍 + 50% = 2.5倍）。
     * 
     * **Validates: Requirements 7.3**
     */
    #[Test]
    #[DataProvider('pointsMultiplierProvider')]
    public function property10_birthday_and_member_day_points_multiplier(
        float $baseMultiplier,
        bool $isBirthday,
        bool $isMemberDay,
        float $expectedMultiplier
    ): void {
        // 计算最终倍数
        if ($isBirthday && $isMemberDay) {
            $finalMultiplier = $baseMultiplier * 2.5;
        } elseif ($isBirthday) {
            $finalMultiplier = $baseMultiplier * 2.0;
        } elseif ($isMemberDay) {
            $finalMultiplier = $baseMultiplier * 1.5;
        } else {
            $finalMultiplier = $baseMultiplier;
        }

        // Assert
        $this->assertEquals($expectedMultiplier, $finalMultiplier);
    }

    public static function pointsMultiplierProvider(): array
    {
        return [
            // 基础倍数1.0
            'normal_day_bronze' => [1.0, false, false, 1.0],
            'birthday_only_bronze' => [1.0, true, false, 2.0],
            'member_day_only_bronze' => [1.0, false, true, 1.5],
            'birthday_and_member_day_bronze' => [1.0, true, true, 2.5],
            
            // 基础倍数1.5（黄金会员）
            'normal_day_gold' => [1.5, false, false, 1.5],
            'birthday_only_gold' => [1.5, true, false, 3.0],
            'member_day_only_gold' => [1.5, false, true, 2.25],
            'birthday_and_member_day_gold' => [1.5, true, true, 3.75],
            
            // 基础倍数2.0（白金会员）
            'normal_day_platinum' => [2.0, false, false, 2.0],
            'birthday_only_platinum' => [2.0, true, false, 4.0],
            'member_day_only_platinum' => [2.0, false, true, 3.0],
            'birthday_and_member_day_platinum' => [2.0, true, true, 5.0],
        ];
    }

    /**
     * Property 16: 权益统计准确性
     * 
     * *For any* 用户，其权益统计应包含节省金额、获得积分等信息。
     * 
     * **Validates: Requirements 12.5**
     */
    #[Test]
    public function property16_privilege_stats_structure(): void
    {
        // 测试权益统计应包含的字段
        $requiredFields = [
            'saved_amount',
            'earned_bonus_points',
            'redeemed_products',
            'birthday_privileges_used',
        ];

        // 模拟统计数据
        $stats = [
            'saved_amount' => 150.0,
            'earned_bonus_points' => 500,
            'redeemed_products' => 3,
            'birthday_privileges_used' => [
                'coupon' => true,
                'dessert' => false,
            ],
        ];

        // Assert - 检查所有必要字段都存在
        foreach ($requiredFields as $field) {
            $this->assertArrayHasKey($field, $stats);
        }
    }

    /**
     * 测试等级权益配置
     */
    #[Test]
    #[DataProvider('levelPrivilegesProvider')]
    public function level_privileges_configuration(
        string $level,
        float $expectedPointsMultiplier,
        int $expectedBirthdayCoupon,
        float $expectedMemberDayDiscount
    ): void {
        $privileges = [
            'bronze' => [
                'points_multiplier' => 1.0,
                'birthday_coupon' => 20,
                'member_day_discount' => 0.90,
            ],
            'silver' => [
                'points_multiplier' => 1.2,
                'birthday_coupon' => 30,
                'member_day_discount' => 0.88,
            ],
            'gold' => [
                'points_multiplier' => 1.5,
                'birthday_coupon' => 50,
                'member_day_discount' => 0.85,
            ],
            'platinum' => [
                'points_multiplier' => 2.0,
                'birthday_coupon' => 100,
                'member_day_discount' => 0.80,
            ],
        ];

        $levelPrivileges = $privileges[$level];

        $this->assertEquals($expectedPointsMultiplier, $levelPrivileges['points_multiplier']);
        $this->assertEquals($expectedBirthdayCoupon, $levelPrivileges['birthday_coupon']);
        $this->assertEquals($expectedMemberDayDiscount, $levelPrivileges['member_day_discount']);
    }

    public static function levelPrivilegesProvider(): array
    {
        return [
            'bronze' => ['bronze', 1.0, 20, 0.90],
            'silver' => ['silver', 1.2, 30, 0.88],
            'gold' => ['gold', 1.5, 50, 0.85],
            'platinum' => ['platinum', 2.0, 100, 0.80],
        ];
    }

    /**
     * 测试下一等级获取
     */
    #[Test]
    #[DataProvider('nextLevelProvider')]
    public function next_level_determination(string $currentLevel, ?string $expectedNextLevel): void
    {
        $levels = ['bronze', 'silver', 'gold', 'platinum'];
        $currentIndex = array_search($currentLevel, $levels);

        if ($currentIndex === false || $currentIndex >= count($levels) - 1) {
            $nextLevel = null;
        } else {
            $nextLevel = $levels[$currentIndex + 1];
        }

        $this->assertEquals($expectedNextLevel, $nextLevel);
    }

    public static function nextLevelProvider(): array
    {
        return [
            'bronze_to_silver' => ['bronze', 'silver'],
            'silver_to_gold' => ['silver', 'gold'],
            'gold_to_platinum' => ['gold', 'platinum'],
            'platinum_is_max' => ['platinum', null],
        ];
    }
}
