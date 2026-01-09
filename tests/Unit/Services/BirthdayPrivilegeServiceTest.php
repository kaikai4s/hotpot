<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\User;
use App\Models\UserBirthday;
use App\Services\BirthdayPrivilegeService;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Unit\DatabaseTestCase;

/**
 * BirthdayPrivilegeService 属性测试
 * 
 * Feature: member-privileges-upgrade
 */
class BirthdayPrivilegeServiceTest extends DatabaseTestCase
{
    private BirthdayPrivilegeService $sut;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sut = new BirthdayPrivilegeService();
    }

    /**
     * Property 1: 生日信息管理一致性
     * 
     * *For any* 用户和任意年份，该用户在该年份内最多只能成功修改一次生日日期。
     * 如果用户在同一年内尝试第二次修改，系统应拒绝并返回错误。
     * 
     * **Validates: Requirements 1.3, 1.4**
     */
    #[Test]
    #[DataProvider('birthdayModificationProvider')]
    public function property1_birthday_modification_limited_to_once_per_year(
        string $firstBirthday,
        string $secondBirthday
    ): void {
        // Arrange
        $user = User::factory()->create();
        $firstDate = Carbon::parse($firstBirthday);
        $secondDate = Carbon::parse($secondBirthday);

        // Act - 第一次设置生日应该成功
        $result = $this->sut->setBirthday($user, $firstDate);
        
        // Assert - 第一次设置成功
        $this->assertInstanceOf(UserBirthday::class, $result);
        $this->assertEquals($firstDate->format('Y-m-d'), $result->birthday->format('Y-m-d'));

        // Act & Assert - 同年内第二次修改应该失败
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('每年只能修改一次生日');
        $this->expectExceptionCode(409);
        
        $this->sut->setBirthday($user, $secondDate);
    }

    /**
     * 生日修改测试数据提供者
     * 模拟属性测试的随机输入
     */
    public static function birthdayModificationProvider(): array
    {
        $testCases = [];
        
        // 生成100个随机测试用例
        for ($i = 0; $i < 100; $i++) {
            $month1 = rand(1, 12);
            $day1 = rand(1, 28);
            $month2 = rand(1, 12);
            $day2 = rand(1, 28);
            
            $testCases["case_{$i}"] = [
                sprintf('1990-%02d-%02d', $month1, $day1),
                sprintf('1991-%02d-%02d', $month2, $day2),
            ];
        }
        
        return $testCases;
    }

    /**
     * Property 1 补充测试：跨年后可以再次修改
     * 
     * **Validates: Requirements 1.3, 1.4**
     */
    #[Test]
    public function property1_birthday_can_be_modified_in_different_year(): void
    {
        // Arrange
        $user = User::factory()->create();
        $birthday = Carbon::parse('1990-06-15');

        // Act - 设置生日
        $this->sut->setBirthday($user, $birthday);

        // 模拟跨年：修改 last_modified_year 为去年
        UserBirthday::where('user_id', $user->id)->update([
            'last_modified_year' => now()->year - 1,
        ]);

        // Act - 新的一年应该可以修改
        $newBirthday = Carbon::parse('1990-07-20');
        $result = $this->sut->setBirthday($user, $newBirthday);

        // Assert
        $this->assertEquals($newBirthday->format('Y-m-d'), $result->birthday->format('Y-m-d'));
        $this->assertEquals(now()->year, $result->last_modified_year);
    }

    /**
     * 测试 canModifyBirthday 方法
     * 
     * **Validates: Requirements 1.3, 1.4**
     */
    #[Test]
    public function canModifyBirthday_returns_true_for_new_user(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $result = $this->sut->canModifyBirthday($user);

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    public function canModifyBirthday_returns_false_after_modification_in_same_year(): void
    {
        // Arrange
        $user = User::factory()->create();
        $birthday = Carbon::parse('1990-06-15');
        $this->sut->setBirthday($user, $birthday);

        // Act
        $result = $this->sut->canModifyBirthday($user);

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    public function canModifyBirthday_returns_true_in_new_year(): void
    {
        // Arrange
        $user = User::factory()->create();
        $birthday = Carbon::parse('1990-06-15');
        $this->sut->setBirthday($user, $birthday);

        // 模拟跨年
        UserBirthday::where('user_id', $user->id)->update([
            'last_modified_year' => now()->year - 1,
        ]);

        // Act
        $result = $this->sut->canModifyBirthday($user);

        // Assert
        $this->assertTrue($result);
    }

    /**
     * 测试 getBirthdayInfo 方法
     */
    #[Test]
    public function getBirthdayInfo_returns_null_for_user_without_birthday(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $result = $this->sut->getBirthdayInfo($user);

        // Assert
        $this->assertNull($result);
    }

    #[Test]
    public function getBirthdayInfo_returns_birthday_info_for_user_with_birthday(): void
    {
        // Arrange
        $user = User::factory()->create();
        $birthday = Carbon::parse('1990-06-15');
        $this->sut->setBirthday($user, $birthday);

        // Act
        $result = $this->sut->getBirthdayInfo($user);

        // Assert
        $this->assertInstanceOf(UserBirthday::class, $result);
        $this->assertEquals($birthday->format('Y-m-d'), $result->birthday->format('Y-m-d'));
    }

    /**
     * Property 3: 生日优惠券发放幂等性
     * 
     * *For any* 用户在同一年内，无论触发多少次生日优惠券发放逻辑，
     * 最多只能获得一张生日优惠券。优惠券面额应与用户等级对应。
     * 
     * **Validates: Requirements 3.1, 3.2, 3.5**
     */
    #[Test]
    #[DataProvider('couponIdempotencyProvider')]
    public function property3_birthday_coupon_issuance_is_idempotent(int $attemptCount): void
    {
        // Arrange
        $user = User::factory()->create();
        $birthday = Carbon::parse('1990-06-15');
        $this->sut->setBirthday($user, $birthday);

        // 创建会员积分记录
        \App\Models\MemberPoint::create([
            'user_id' => $user->id,
            'total_points' => 0,
            'available_points' => 0,
            'frozen_points' => 0,
            'level' => 'bronze',
        ]);

        // Act - 多次尝试发放优惠券
        $coupons = [];
        for ($i = 0; $i < $attemptCount; $i++) {
            $coupon = $this->sut->issueBirthdayCoupon($user);
            if ($coupon) {
                $coupons[] = $coupon;
            }
        }

        // Assert - 只应该成功发放一张
        $this->assertCount(1, $coupons);
        
        // 验证数据库中只有一条记录
        $logCount = \App\Models\BirthdayPrivilegeLog::where('user_id', $user->id)
            ->where('year', now()->year)
            ->where('privilege_type', 'coupon')
            ->count();
        $this->assertEquals(1, $logCount);
    }

    /**
     * 优惠券幂等性测试数据提供者
     */
    public static function couponIdempotencyProvider(): array
    {
        $testCases = [];
        
        // 测试不同的尝试次数
        for ($i = 2; $i <= 10; $i++) {
            $testCases["attempt_{$i}_times"] = [$i];
        }
        
        return $testCases;
    }

    /**
     * Property 3 补充测试：优惠券面额与会员等级对应
     * 
     * **Validates: Requirements 3.2**
     */
    #[Test]
    #[DataProvider('couponAmountByLevelProvider')]
    public function property3_coupon_amount_matches_member_level(string $level, int $expectedAmount): void
    {
        // Act
        $amount = $this->sut->getBirthdayCouponAmount($level);

        // Assert
        $this->assertEquals($expectedAmount, $amount);
    }

    public static function couponAmountByLevelProvider(): array
    {
        return [
            'bronze' => ['bronze', 20],
            'silver' => ['silver', 30],
            'gold' => ['gold', 50],
            'platinum' => ['platinum', 100],
        ];
    }

    /**
     * Property 4: 生日优惠券有效期计算
     * 
     * *For any* 生日优惠券，其过期时间应等于用户生日日期加30天。
     * 
     * **Validates: Requirements 3.4**
     */
    #[Test]
    #[DataProvider('couponExpiryProvider')]
    public function property4_birthday_coupon_expires_30_days_after_birthday(
        int $birthMonth,
        int $birthDay
    ): void {
        // Arrange
        $user = User::factory()->create();
        $birthday = Carbon::create(1990, $birthMonth, $birthDay);
        $this->sut->setBirthday($user, $birthday);

        \App\Models\MemberPoint::create([
            'user_id' => $user->id,
            'total_points' => 0,
            'available_points' => 0,
            'frozen_points' => 0,
            'level' => 'bronze',
        ]);

        // Act
        $userCoupon = $this->sut->issueBirthdayCoupon($user);

        // Assert
        $this->assertNotNull($userCoupon);
        
        // 计算预期过期时间
        $thisYearBirthday = Carbon::create(now()->year, $birthMonth, $birthDay);
        $expectedExpiry = $thisYearBirthday->copy()->addDays(30);
        
        $this->assertEquals(
            $expectedExpiry->format('Y-m-d'),
            $userCoupon->expires_at->format('Y-m-d')
        );
    }

    public static function couponExpiryProvider(): array
    {
        $testCases = [];
        
        // 生成多个随机生日日期
        for ($i = 0; $i < 50; $i++) {
            $month = rand(1, 12);
            $day = rand(1, 28);
            $testCases["birthday_{$month}_{$day}"] = [$month, $day];
        }
        
        return $testCases;
    }

    /**
     * 测试 isBirthday 方法
     */
    #[Test]
    public function isBirthday_returns_true_on_birthday(): void
    {
        // Arrange
        $user = User::factory()->create();
        $today = Carbon::today();
        $birthday = Carbon::create(1990, $today->month, $today->day);
        $this->sut->setBirthday($user, $birthday);

        // Act
        $result = $this->sut->isBirthday($user);

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    public function isBirthday_returns_false_on_non_birthday(): void
    {
        // Arrange
        $user = User::factory()->create();
        $tomorrow = Carbon::tomorrow();
        $birthday = Carbon::create(1990, $tomorrow->month, $tomorrow->day);
        $this->sut->setBirthday($user, $birthday);

        // Act
        $result = $this->sut->isBirthday($user);

        // Assert
        $this->assertFalse($result);
    }

    /**
     * 测试 isInBirthdayPeriod 方法
     */
    #[Test]
    public function isInBirthdayPeriod_returns_true_within_7_days_after_birthday(): void
    {
        // Arrange
        $user = User::factory()->create();
        $fiveDaysAgo = Carbon::today()->subDays(5);
        $birthday = Carbon::create(1990, $fiveDaysAgo->month, $fiveDaysAgo->day);
        $this->sut->setBirthday($user, $birthday);

        // Act
        $result = $this->sut->isInBirthdayPeriod($user);

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    public function isInBirthdayPeriod_returns_false_after_7_days(): void
    {
        // Arrange
        $user = User::factory()->create();
        $tenDaysAgo = Carbon::today()->subDays(10);
        $birthday = Carbon::create(1990, $tenDaysAgo->month, $tenDaysAgo->day);
        $this->sut->setBirthday($user, $birthday);

        // Act
        $result = $this->sut->isInBirthdayPeriod($user);

        // Assert
        $this->assertFalse($result);
    }

    /**
     * Property 5: 生日甜品券唯一性
     * 
     * *For any* 用户在同一年内，最多只能获得一份生日甜品券。
     * 甜品券使用后状态应更新为"used"，防止重复使用。
     * 
     * **Validates: Requirements 4.2, 4.3**
     */
    #[Test]
    #[DataProvider('dessertVoucherIdempotencyProvider')]
    public function property5_birthday_dessert_voucher_is_unique_per_year(int $attemptCount): void
    {
        // Arrange
        $user = User::factory()->create();
        $birthday = Carbon::parse('1990-06-15');
        $this->sut->setBirthday($user, $birthday);

        // Act - 多次尝试发放甜品券
        $vouchers = [];
        for ($i = 0; $i < $attemptCount; $i++) {
            $voucher = $this->sut->issueBirthdayDessertVoucher($user);
            if ($voucher) {
                $vouchers[] = $voucher;
            }
        }

        // Assert - 只应该成功发放一张
        $this->assertCount(1, $vouchers);
        
        // 验证数据库中只有一条记录
        $voucherCount = \App\Models\BirthdayDessertVoucher::where('user_id', $user->id)
            ->where('year', now()->year)
            ->count();
        $this->assertEquals(1, $voucherCount);
    }

    public static function dessertVoucherIdempotencyProvider(): array
    {
        $testCases = [];
        for ($i = 2; $i <= 10; $i++) {
            $testCases["attempt_{$i}_times"] = [$i];
        }
        return $testCases;
    }

    /**
     * Property 5 补充测试：甜品券使用后状态更新
     * 
     * **Validates: Requirements 4.3**
     */
    #[Test]
    public function property5_dessert_voucher_status_updates_to_used(): void
    {
        // Arrange
        $user = User::factory()->create();
        $birthday = Carbon::parse('1990-06-15');
        $this->sut->setBirthday($user, $birthday);
        
        $voucher = $this->sut->issueBirthdayDessertVoucher($user);
        $this->assertNotNull($voucher);
        $this->assertEquals('unused', $voucher->status);

        // Act
        $this->sut->useDessertVoucher($voucher, 12345);

        // Assert
        $voucher->refresh();
        $this->assertEquals('used', $voucher->status);
        $this->assertNotNull($voucher->used_at);
        $this->assertEquals(12345, $voucher->order_id);
    }

    /**
     * Property 5 补充测试：已使用的甜品券不能再次使用
     * 
     * **Validates: Requirements 4.3**
     */
    #[Test]
    public function property5_used_dessert_voucher_cannot_be_used_again(): void
    {
        // Arrange
        $user = User::factory()->create();
        $birthday = Carbon::parse('1990-06-15');
        $this->sut->setBirthday($user, $birthday);
        
        $voucher = $this->sut->issueBirthdayDessertVoucher($user);
        $this->sut->useDessertVoucher($voucher, 12345);

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('甜品券已使用或已过期');
        
        $this->sut->useDessertVoucher($voucher, 67890);
    }

    /**
     * Property 6: 生日甜品券有效期延长
     * 
     * *For any* 生日甜品券，如果用户在生日当天未使用，其有效期应延长至生日后7天。
     * 
     * **Validates: Requirements 4.4**
     */
    #[Test]
    #[DataProvider('dessertVoucherExpiryProvider')]
    public function property6_dessert_voucher_expires_7_days_after_birthday(
        int $birthMonth,
        int $birthDay
    ): void {
        // Arrange
        $user = User::factory()->create();
        $birthday = Carbon::create(1990, $birthMonth, $birthDay);
        $this->sut->setBirthday($user, $birthday);

        // Act
        $voucher = $this->sut->issueBirthdayDessertVoucher($user);

        // Assert
        $this->assertNotNull($voucher);
        
        // 计算预期过期时间（生日后7天的23:59:59）
        $thisYearBirthday = Carbon::create(now()->year, $birthMonth, $birthDay);
        $expectedExpiry = $thisYearBirthday->copy()->addDays(7)->endOfDay();
        
        $this->assertEquals(
            $expectedExpiry->format('Y-m-d'),
            $voucher->expires_at->format('Y-m-d')
        );
    }

    public static function dessertVoucherExpiryProvider(): array
    {
        $testCases = [];
        for ($i = 0; $i < 50; $i++) {
            $month = rand(1, 12);
            $day = rand(1, 28);
            $testCases["birthday_{$month}_{$day}"] = [$month, $day];
        }
        return $testCases;
    }

    /**
     * Property 2: 生日积分双倍计算正确性
     * 
     * *For any* 用户在生日当天完成的订单，其获得的积分应等于：
     * `订单金额 × 基础比例 × 会员等级倍数 × 2`。
     * 
     * **Validates: Requirements 2.1, 2.2, 2.3**
     */
    #[Test]
    public function property2_birthday_points_multiplier_is_double(): void
    {
        // Arrange
        $user = User::factory()->create();
        $today = Carbon::today();
        $birthday = Carbon::create(1990, $today->month, $today->day);
        $this->sut->setBirthday($user, $birthday);

        // Act
        $multiplier = $this->sut->calculateBirthdayPointsMultiplier($user);

        // Assert - 生日当天应该是2倍
        $this->assertEquals(2.0, $multiplier);
    }

    #[Test]
    public function property2_non_birthday_points_multiplier_is_one(): void
    {
        // Arrange
        $user = User::factory()->create();
        $tomorrow = Carbon::tomorrow();
        $birthday = Carbon::create(1990, $tomorrow->month, $tomorrow->day);
        $this->sut->setBirthday($user, $birthday);

        // Act
        $multiplier = $this->sut->calculateBirthdayPointsMultiplier($user);

        // Assert - 非生日应该是1倍
        $this->assertEquals(1.0, $multiplier);
    }

    /**
     * Property 2 补充测试：指定日期的积分倍数计算
     * 
     * **Validates: Requirements 2.1, 2.2**
     */
    #[Test]
    #[DataProvider('birthdayMultiplierProvider')]
    public function property2_birthday_multiplier_for_specific_date(
        int $birthMonth,
        int $birthDay,
        int $checkMonth,
        int $checkDay,
        float $expectedMultiplier
    ): void {
        // Arrange
        $user = User::factory()->create();
        $birthday = Carbon::create(1990, $birthMonth, $birthDay);
        $this->sut->setBirthday($user, $birthday);
        
        $checkDate = Carbon::create(now()->year, $checkMonth, $checkDay);

        // Act
        $multiplier = $this->sut->calculateBirthdayPointsMultiplier($user, $checkDate);

        // Assert
        $this->assertEquals($expectedMultiplier, $multiplier);
    }

    public static function birthdayMultiplierProvider(): array
    {
        $testCases = [];
        
        // 生成随机测试用例
        for ($i = 0; $i < 50; $i++) {
            $birthMonth = rand(1, 12);
            $birthDay = rand(1, 28);
            $checkMonth = rand(1, 12);
            $checkDay = rand(1, 28);
            
            // 如果月日相同，期望倍数为2.0，否则为1.0
            $expectedMultiplier = ($birthMonth === $checkMonth && $birthDay === $checkDay) ? 2.0 : 1.0;
            
            $testCases["birth_{$birthMonth}_{$birthDay}_check_{$checkMonth}_{$checkDay}"] = [
                $birthMonth,
                $birthDay,
                $checkMonth,
                $checkDay,
                $expectedMultiplier,
            ];
        }
        
        // 添加确定性测试用例
        $testCases['same_date'] = [6, 15, 6, 15, 2.0];
        $testCases['different_month'] = [6, 15, 7, 15, 1.0];
        $testCases['different_day'] = [6, 15, 6, 16, 1.0];
        
        return $testCases;
    }
}
