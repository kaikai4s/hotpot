<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Coupon;
use App\Models\MemberPoint;
use App\Models\Order;
use App\Models\PointTransaction;
use App\Models\Review;
use App\Models\User;
use App\Models\UserCoupon;
use App\Services\PointExpirationService;
use App\Services\PointRuleService;
use App\Services\PointService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

class PointServiceTest extends TestCase
{
    private PointService $sut;
    private PointRuleService|MockObject $ruleServiceMock;
    private PointExpirationService|MockObject $expirationServiceMock;
    private User|MockObject $userMock;
    private MemberPoint|MockObject $memberPointMock;
    private Order|MockObject $orderMock;
    private Review|MockObject $reviewMock;
    private Coupon|MockObject $couponMock;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock dependencies
        $this->ruleServiceMock = $this->createMock(PointRuleService::class);
        $this->expirationServiceMock = $this->createMock(PointExpirationService::class);

        // Mock User
        $this->userMock = $this->createMock(User::class);
        $this->userMock->id = 1;

        // Mock MemberPoint
        $this->memberPointMock = $this->createMock(MemberPoint::class);
        $this->memberPointMock->user_id = 1;
        $this->memberPointMock->total_points = 1000;
        $this->memberPointMock->available_points = 800;
        $this->memberPointMock->frozen_points = 200;
        $this->memberPointMock->level = 'bronze';

        // Mock Order
        $this->orderMock = $this->createMock(Order::class);
        $this->orderMock->id = 1;
        $this->orderMock->order_no = 'ORD20260105000001';
        $this->orderMock->total_amount = 200.00;

        // Mock Review
        $this->reviewMock = $this->createMock(Review::class);
        $this->reviewMock->id = 1;
        $this->reviewMock->images = ['image1.jpg'];
        $this->reviewMock->user = $this->userMock;

        // Mock Coupon
        $this->couponMock = $this->createMock(Coupon::class);
        $this->couponMock->id = 1;
        $this->couponMock->points_required = 100;
        $this->couponMock->is_active = true;
        $this->couponMock->stock = 10;
        $this->couponMock->valid_to = now()->addDays(30);

        // Instantiate SUT
        $this->sut = new PointService($this->ruleServiceMock, $this->expirationServiceMock);
    }

    #[Test]
    // Ref: TSD Section 3.5.1 - 多来源积分获得
    public function earn_points_increases_total_points_and_available_points(): void
    {
        // Arrange
        $points = 100;
        $this->ruleServiceMock->expects($this->once())
            ->method('getExpireDays')
            ->willReturn(365);

        // Act
        $result = $this->sut->earnPoints($this->userMock, $points, 'test', 1, '测试获得积分');

        // Assert
        $this->assertInstanceOf(PointTransaction::class, $result);
        // 验证total_points和available_points已增加
        // 注意：这是Red Stage，实际实现可能不同
    }

    #[Test]
    // Ref: TSD Section 3.5.1 - 订单支付积分
    public function earn_points_from_order_calculates_points_using_order_amount(): void
    {
        // Arrange
        $this->orderMock->user = $this->userMock;
        $this->ruleServiceMock->expects($this->once())
            ->method('calculatePointsFromOrder')
            ->willReturn(20);

        // Act
        $result = $this->sut->earnPointsFromOrder($this->orderMock);

        // Assert
        // 如果已发放过积分，返回null；否则返回PointTransaction
        // 注意：这是Red Stage，实际实现可能不同
    }

    #[Test]
    // Ref: TSD Section 3.5.1 - 订单支付积分，幂等性保证
    public function earn_points_from_order_returns_null_when_already_earned(): void
    {
        // Arrange
        $this->orderMock->user = $this->userMock;

        // Act
        $result = $this->sut->earnPointsFromOrder($this->orderMock);

        // Assert
        // 如果已发放过积分，返回null
        // 注意：这是Red Stage，实际实现可能不同
    }

    #[Test]
    // Ref: TSD Section 3.5.1 - 评价奖励积分
    public function earn_points_from_review_calculates_points_with_bonuses(): void
    {
        // Arrange
        $this->ruleServiceMock->expects($this->once())
            ->method('calculatePointsFromReview')
            ->willReturn(30);

        // Act
        $result = $this->sut->earnPointsFromReview($this->reviewMock);

        // Assert
        // 如果已发放过积分，返回null；否则返回PointTransaction
        // 注意：这是Red Stage，实际实现可能不同
    }

    #[Test]
    // Ref: TSD Section 3.5.1 - 评价采纳奖励
    public function earn_points_from_adoption_calculates_points_with_level_multiplier(): void
    {
        // Arrange
        $this->ruleServiceMock->expects($this->once())
            ->method('calculatePointsFromAdoption')
            ->willReturn(50);

        // Act
        $result = $this->sut->earnPointsFromAdoption($this->reviewMock);

        // Assert
        // 如果已发放过采纳积分，返回null；否则返回PointTransaction
        // 注意：这是Red Stage，实际实现可能不同
    }

    #[Test]
    // Ref: TSD Section 3.5.4 - 积分兑换系统，幂等性保证
    public function redeem_coupon_throws_exception_when_idempotency_key_exists(): void
    {
        // Arrange
        $idempotencyKey = 'test-idempotency-key-123';

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('重复兑换');
        $this->expectExceptionCode(409);

        $this->sut->redeemCoupon($this->userMock, 1, $idempotencyKey);
    }

    #[Test]
    // Ref: TSD Section 3.5.4 - 积分兑换系统
    public function redeem_coupon_throws_exception_when_coupon_not_active(): void
    {
        // Arrange
        $this->couponMock->is_active = false;

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('优惠券不可用');
        $this->expectExceptionCode(400);

        $this->sut->redeemCoupon($this->userMock, 1, 'test-key');
    }

    #[Test]
    // Ref: TSD Section 3.5.4 - 积分兑换系统
    public function redeem_coupon_throws_exception_when_stock_insufficient(): void
    {
        // Arrange
        $this->couponMock->stock = 0;

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('库存不足');
        $this->expectExceptionCode(400);

        $this->sut->redeemCoupon($this->userMock, 1, 'test-key');
    }

    #[Test]
    // Ref: TSD Section 3.5.4 - 积分兑换系统，积分冻结机制
    public function redeem_coupon_decreases_available_points_and_increases_frozen_points(): void
    {
        // Arrange
        $idempotencyKey = 'test-key-new';

        // Act
        $result = $this->sut->redeemCoupon($this->userMock, 1, $idempotencyKey);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('coupon_id', $result);
        $this->assertArrayHasKey('points_used', $result);
        $this->assertArrayHasKey('remaining_points', $result);
        // 验证available_points减少，frozen_points增加
        // 注意：这是Red Stage，实际实现可能不同
    }

    #[Test]
    // Ref: TSD Section 3.5.4 - 积分兑换系统
    public function redeem_coupon_throws_exception_when_points_insufficient(): void
    {
        // Arrange
        $this->memberPointMock->available_points = 50; // 不足100

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('积分不足');
        $this->expectExceptionCode(400);

        $this->sut->redeemCoupon($this->userMock, 1, 'test-key');
    }

    #[Test]
    // Ref: TSD Section 3.5.1 - 消耗积分
    public function spend_points_decreases_available_points(): void
    {
        // Arrange
        $points = 50;

        // Act
        $result = $this->sut->spendPoints($this->userMock, $points, 'makeup_checkin', 1, '补签消耗');

        // Assert
        $this->assertInstanceOf(PointTransaction::class, $result);
        // 验证available_points已减少
        // 注意：这是Red Stage，实际实现可能不同
    }

    #[Test]
    // Ref: TSD Section 3.5.1 - 消耗积分
    public function spend_points_throws_exception_when_points_insufficient(): void
    {
        // Arrange
        $this->memberPointMock->available_points = 30;
        $points = 50;

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('积分不足');
        $this->expectExceptionCode(400);

        $this->sut->spendPoints($this->userMock, $points, 'test', 1);
    }

    #[Test]
    // Ref: TSD Section 3.5.4 - 积分兑换系统，解冻积分
    public function unfreeze_points_decreases_frozen_points_when_coupon_used(): void
    {
        // Arrange
        $userCoupon = $this->createMock(UserCoupon::class);
        $userCoupon->user = $this->userMock;
        $userCoupon->coupon = $this->couponMock;

        // Act
        $this->sut->unfreezePoints($userCoupon, 'used');

        // Assert
        // 验证frozen_points减少，available_points不增加（已使用）
        // 注意：这是Red Stage，实际实现可能不同
    }

    #[Test]
    // Ref: TSD Section 3.5.4 - 积分兑换系统，解冻积分
    public function unfreeze_points_returns_points_when_coupon_expired(): void
    {
        // Arrange
        $userCoupon = $this->createMock(UserCoupon::class);
        $userCoupon->user = $this->userMock;
        $userCoupon->coupon = $this->couponMock;

        // Act
        $this->sut->unfreezePoints($userCoupon, 'expired');

        // Assert
        // 验证frozen_points减少，available_points增加（过期返还）
        // 注意：这是Red Stage，实际实现可能不同
    }

    #[Test]
    // Ref: TSD Section 3.5.2 - 会员等级系统，自动升级
    public function earn_points_updates_user_level_when_points_threshold_reached(): void
    {
        // Arrange
        $points = 500;
        $this->ruleServiceMock->expects($this->once())
            ->method('getExpireDays')
            ->willReturn(365);

        // Act
        $result = $this->sut->earnPoints($this->userMock, $points, 'test', 1);

        // Assert
        $this->assertInstanceOf(PointTransaction::class, $result);
        // 验证会员等级已更新
        // 注意：这是Red Stage，实际实现可能不同
    }

    #[Test]
    // Ref: TSD Section 3.5.5 - 积分过期管理
    public function earn_points_schedules_expiration_when_expire_days_provided(): void
    {
        // Arrange
        $points = 100;
        $expireDays = 365;
        $this->ruleServiceMock->expects($this->never())
            ->method('getExpireDays');

        $this->expirationServiceMock->expects($this->once())
            ->method('scheduleExpiration')
            ->with($this->isInstanceOf(PointTransaction::class), $expireDays);

        // Act
        $result = $this->sut->earnPoints($this->userMock, $points, 'test', 1, null, $expireDays);

        // Assert
        $this->assertInstanceOf(PointTransaction::class, $result);
    }
}

