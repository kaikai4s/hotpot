<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\MemberDayConfig;
use App\Services\MemberDayService;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Unit\DatabaseTestCase;

/**
 * MemberDayService 属性测试
 * 
 * Feature: member-privileges-upgrade
 */
class MemberDayServiceTest extends DatabaseTestCase
{
    private MemberDayService $sut;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sut = new MemberDayService();
    }

    /**
     * Property 15: 会员日倒计时计算
     * 
     * *For any* 日期，系统应正确计算距离下次会员日的天数。
     * 如果当天是会员日，倒计时应为0。
     * 
     * **Validates: Requirements 12.4**
     */
    #[Test]
    public function property15_member_day_countdown_is_zero_on_member_day(): void
    {
        // Arrange
        MemberDayConfig::create([
            'day_of_month' => 8,
            'is_enabled' => true,
            'base_discount' => 0.88,
            'points_bonus_rate' => 0.50,
        ]);

        // 设置今天为8号
        Carbon::setTestNow(Carbon::create(2026, 1, 8));

        // Act
        $days = $this->sut->getDaysUntilMemberDay();

        // Assert
        $this->assertEquals(0, $days);

        Carbon::setTestNow();
    }

    #[Test]
    #[DataProvider('countdownProvider')]
    public function property15_member_day_countdown_calculation(
        int $currentDay,
        int $memberDay,
        int $expectedDays
    ): void {
        // Arrange
        MemberDayConfig::create([
            'day_of_month' => $memberDay,
            'is_enabled' => true,
            'base_discount' => 0.88,
            'points_bonus_rate' => 0.50,
        ]);

        Carbon::setTestNow(Carbon::create(2026, 1, $currentDay));

        // Act
        $days = $this->sut->getDaysUntilMemberDay();

        // Assert
        $this->assertEquals($expectedDays, $days);

        Carbon::setTestNow();
    }

    public static function countdownProvider(): array
    {
        return [
            'on_member_day' => [8, 8, 0],
            'one_day_before' => [7, 8, 1],
            'five_days_before' => [3, 8, 5],
            'day_after_member_day' => [9, 8, 30], // 下个月8号
            'mid_month_after' => [15, 8, 24], // 下个月8号
        ];
    }

    /**
     * 测试 isMemberDay 方法
     */
    #[Test]
    public function isMemberDay_returns_true_on_configured_day(): void
    {
        // Arrange
        MemberDayConfig::create([
            'day_of_month' => 15,
            'is_enabled' => true,
            'base_discount' => 0.88,
            'points_bonus_rate' => 0.50,
        ]);

        $memberDay = Carbon::create(2026, 1, 15);

        // Act
        $result = $this->sut->isMemberDay($memberDay);

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    public function isMemberDay_returns_false_on_non_member_day(): void
    {
        // Arrange
        MemberDayConfig::create([
            'day_of_month' => 8,
            'is_enabled' => true,
            'base_discount' => 0.88,
            'points_bonus_rate' => 0.50,
        ]);

        $nonMemberDay = Carbon::create(2026, 1, 10);

        // Act
        $result = $this->sut->isMemberDay($nonMemberDay);

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    public function isMemberDay_returns_false_when_disabled(): void
    {
        // Arrange
        MemberDayConfig::create([
            'day_of_month' => 8,
            'is_enabled' => false,
            'base_discount' => 0.88,
            'points_bonus_rate' => 0.50,
        ]);

        $memberDay = Carbon::create(2026, 1, 8);

        // Act
        $result = $this->sut->isMemberDay($memberDay);

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    public function isMemberDay_uses_override_when_set(): void
    {
        // Arrange
        MemberDayConfig::create([
            'day_of_month' => 8,
            'is_enabled' => true,
            'base_discount' => 0.88,
            'points_bonus_rate' => 0.50,
            'current_month_override' => 15,
        ]);

        // Act & Assert
        $this->assertFalse($this->sut->isMemberDay(Carbon::create(2026, 1, 8)));
        $this->assertTrue($this->sut->isMemberDay(Carbon::create(2026, 1, 15)));
    }

    /**
     * 测试 getNextMemberDay 方法
     */
    #[Test]
    public function getNextMemberDay_returns_this_month_if_not_passed(): void
    {
        // Arrange
        MemberDayConfig::create([
            'day_of_month' => 20,
            'is_enabled' => true,
            'base_discount' => 0.88,
            'points_bonus_rate' => 0.50,
        ]);

        Carbon::setTestNow(Carbon::create(2026, 1, 10));

        // Act
        $nextMemberDay = $this->sut->getNextMemberDay();

        // Assert
        $this->assertEquals('2026-01-20', $nextMemberDay->format('Y-m-d'));

        Carbon::setTestNow();
    }

    #[Test]
    public function getNextMemberDay_returns_next_month_if_passed(): void
    {
        // Arrange
        MemberDayConfig::create([
            'day_of_month' => 8,
            'is_enabled' => true,
            'base_discount' => 0.88,
            'points_bonus_rate' => 0.50,
        ]);

        Carbon::setTestNow(Carbon::create(2026, 1, 15));

        // Act
        $nextMemberDay = $this->sut->getNextMemberDay();

        // Assert
        $this->assertEquals('2026-02-08', $nextMemberDay->format('Y-m-d'));

        Carbon::setTestNow();
    }

    /**
     * Property 7: 会员日折扣计算正确性
     * 
     * *For any* 用户在会员日下单，其折扣应根据会员等级计算
     * （青铜0.9、白银0.88、黄金0.85、白金0.8）
     * 
     * **Validates: Requirements 6.1, 6.2, 6.3**
     */
    #[Test]
    #[DataProvider('memberDayDiscountProvider')]
    public function property7_member_day_discount_by_level(
        string $level,
        float $expectedDiscount
    ): void {
        // Arrange
        MemberDayConfig::create([
            'day_of_month' => 8,
            'is_enabled' => true,
            'base_discount' => 0.88,
            'points_bonus_rate' => 0.50,
            'discount_by_level' => [
                'bronze' => 0.90,
                'silver' => 0.88,
                'gold' => 0.85,
                'platinum' => 0.80,
            ],
        ]);

        // Act
        $discount = $this->sut->getMemberDayDiscount($level);

        // Assert
        $this->assertEquals($expectedDiscount, $discount);
    }

    public static function memberDayDiscountProvider(): array
    {
        return [
            'bronze_level' => ['bronze', 0.90],
            'silver_level' => ['silver', 0.88],
            'gold_level' => ['gold', 0.85],
            'platinum_level' => ['platinum', 0.80],
        ];
    }

    #[Test]
    #[DataProvider('discountAmountProvider')]
    public function property7_member_day_discount_amount_calculation(
        float $originalAmount,
        string $level,
        float $expectedDiscountAmount
    ): void {
        // Arrange
        MemberDayConfig::create([
            'day_of_month' => 8,
            'is_enabled' => true,
            'base_discount' => 0.88,
            'points_bonus_rate' => 0.50,
            'discount_by_level' => [
                'bronze' => 0.90,
                'silver' => 0.88,
                'gold' => 0.85,
                'platinum' => 0.80,
            ],
        ]);

        // Act
        $discountAmount = $this->sut->calculateMemberDayDiscountAmount($originalAmount, $level);

        // Assert
        $this->assertEquals($expectedDiscountAmount, $discountAmount);
    }

    public static function discountAmountProvider(): array
    {
        return [
            'bronze_100' => [100.0, 'bronze', 10.0],   // 100 * (1 - 0.90) = 10
            'silver_100' => [100.0, 'silver', 12.0],  // 100 * (1 - 0.88) = 12
            'gold_100' => [100.0, 'gold', 15.0],      // 100 * (1 - 0.85) = 15
            'platinum_100' => [100.0, 'platinum', 20.0], // 100 * (1 - 0.80) = 20
            'platinum_500' => [500.0, 'platinum', 100.0], // 500 * (1 - 0.80) = 100
        ];
    }

    /**
     * Property 8: 会员日折扣最优选择
     * 
     * *For any* 订单，当存在多个折扣时，系统应选择对用户最优惠的折扣应用。
     * 
     * **Validates: Requirements 6.5**
     */
    #[Test]
    #[DataProvider('bestDiscountProvider')]
    public function property8_member_day_best_discount_selection(
        float $memberDayDiscount,
        float $otherDiscount,
        float $expectedBestDiscount
    ): void {
        // Arrange
        MemberDayConfig::create([
            'day_of_month' => 8,
            'is_enabled' => true,
            'base_discount' => 0.88,
            'points_bonus_rate' => 0.50,
        ]);

        // Act
        $bestDiscount = $this->sut->getBestDiscount($memberDayDiscount, $otherDiscount);

        // Assert
        // 折扣值越小，优惠越大，所以选择最小值
        $this->assertEquals($expectedBestDiscount, $bestDiscount);
    }

    public static function bestDiscountProvider(): array
    {
        return [
            'member_day_better' => [0.85, 0.90, 0.85],
            'other_better' => [0.88, 0.80, 0.80],
            'equal_discounts' => [0.85, 0.85, 0.85],
            'platinum_vs_coupon' => [0.80, 0.75, 0.75],
        ];
    }

    /**
     * Property 9: 会员日积分加成计算
     * 
     * *For any* 用户在会员日完成的订单，其获得的积分应在基础积分上额外增加50%。
     * 
     * **Validates: Requirements 7.1, 7.2**
     */
    #[Test]
    public function property9_member_day_points_bonus_rate(): void
    {
        // Arrange
        MemberDayConfig::create([
            'day_of_month' => 8,
            'is_enabled' => true,
            'base_discount' => 0.88,
            'points_bonus_rate' => 0.50,
        ]);

        // Act
        $bonusRate = $this->sut->getMemberDayPointsBonus();

        // Assert
        $this->assertEquals(0.50, $bonusRate);
    }

    #[Test]
    #[DataProvider('memberDayPointsProvider')]
    public function property9_member_day_points_calculation(
        int $basePoints,
        int $expectedTotalPoints
    ): void {
        // Arrange
        MemberDayConfig::create([
            'day_of_month' => 8,
            'is_enabled' => true,
            'base_discount' => 0.88,
            'points_bonus_rate' => 0.50,
        ]);

        // Act
        $totalPoints = $this->sut->calculateMemberDayPoints($basePoints);

        // Assert
        // 基础积分 + 50% 加成 = 基础积分 * 1.5
        $this->assertEquals($expectedTotalPoints, $totalPoints);
    }

    public static function memberDayPointsProvider(): array
    {
        return [
            'base_100' => [100, 150],    // 100 * 1.5 = 150
            'base_200' => [200, 300],    // 200 * 1.5 = 300
            'base_50' => [50, 75],       // 50 * 1.5 = 75
            'base_1000' => [1000, 1500], // 1000 * 1.5 = 1500
            'base_33' => [33, 49],       // 33 * 1.5 = 49.5 -> 49 (向下取整)
        ];
    }
}
