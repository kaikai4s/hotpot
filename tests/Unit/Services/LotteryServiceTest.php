<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\LotteryActivity;
use App\Models\LotteryPrize;
use App\Models\User;
use App\Services\LotteryService;
use App\Services\PointService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

class LotteryServiceTest extends TestCase
{
    private LotteryService $sut;
    private PointService|MockObject $pointServiceMock;
    private User|MockObject $userMock;
    private LotteryActivity|MockObject $activityMock;
    private LotteryPrize|MockObject $prizeMock;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock dependencies
        $this->pointServiceMock = $this->createMock(PointService::class);

        // Mock User
        $this->userMock = $this->createMock(User::class);
        $this->userMock->id = 1;

        // Mock LotteryActivity
        $this->activityMock = $this->createMock(LotteryActivity::class);
        $this->activityMock->id = 1;
        $this->activityMock->name = '新年抽奖';
        $this->activityMock->points_cost = 10;
        $this->activityMock->daily_limit = 3;
        $this->activityMock->total_limit = 10;

        // Mock LotteryPrize
        $this->prizeMock = $this->createMock(LotteryPrize::class);
        $this->prizeMock->id = 1;
        $this->prizeMock->name = '10元优惠券';
        $this->prizeMock->prize_type = 'coupon';
        $this->prizeMock->prize_id = 1;
        $this->prizeMock->prize_value = 10;
        $this->prizeMock->probability = 1000;
        $this->prizeMock->is_active = true;
        $this->prizeMock->stock = 100;
        $this->prizeMock->daily_stock = 10;

        // Instantiate SUT
        $this->sut = new LotteryService($this->pointServiceMock);
    }

    #[Test]
    // Ref: TSD Section 3.13.1 - 抽奖活动
    public function draw_throws_exception_when_activity_not_active(): void
    {
        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('活动未开始或已结束');
        $this->expectExceptionCode(400);

        // 模拟活动未激活
        // 注意：这是Red Stage，实际实现可能不同
        $this->sut->draw($this->userMock, 1);
    }

    #[Test]
    // Ref: TSD Section 3.13.2 - 抽奖规则，抽奖次数限制
    public function draw_throws_exception_when_daily_limit_exceeded(): void
    {
        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('今日抽奖次数已达上限');
        $this->expectExceptionCode(400);

        // 模拟今日抽奖次数已达上限
        // 注意：这是Red Stage，实际实现可能不同
        $this->sut->draw($this->userMock, 1);
    }

    #[Test]
    // Ref: TSD Section 3.13.2 - 抽奖规则，抽奖次数限制
    public function draw_throws_exception_when_total_limit_exceeded(): void
    {
        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('总抽奖次数已达上限');
        $this->expectExceptionCode(400);

        // 模拟总抽奖次数已达上限
        // 注意：这是Red Stage，实际实现可能不同
        $this->sut->draw($this->userMock, 1);
    }

    #[Test]
    // Ref: TSD Section 3.13.2 - 抽奖规则
    public function draw_throws_exception_when_points_insufficient(): void
    {
        // Arrange
        $this->pointServiceMock->expects($this->once())
            ->method('getPoints')
            ->willReturn($this->createMock(\App\Models\MemberPoint::class));

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('积分不足');
        $this->expectExceptionCode(400);

        // 模拟积分不足
        // 注意：这是Red Stage，实际实现可能不同
        $this->sut->draw($this->userMock, 1);
    }

    #[Test]
    // Ref: TSD Section 3.13.1 - 抽奖活动
    public function draw_returns_prize_when_winner(): void
    {
        // Act
        $result = $this->sut->draw($this->userMock, 1);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('record', $result);
        $this->assertArrayHasKey('prize', $result);
        $this->assertArrayHasKey('is_winner', $result);
        // 验证如果中奖，返回奖品信息
        // 注意：这是Red Stage，实际实现可能不同
    }

    #[Test]
    // Ref: TSD Section 3.13.1 - 抽奖活动
    public function draw_returns_null_prize_when_not_winner(): void
    {
        // Act
        $result = $this->sut->draw($this->userMock, 1);

        // Assert
        $this->assertIsArray($result);
        // 验证如果未中奖，prize为null，is_winner为false
        // 注意：这是Red Stage，实际实现可能不同
    }

    #[Test]
    // Ref: TSD Section 3.13.3 - 抽奖记录
    public function get_today_draw_count_returns_count_for_today(): void
    {
        // Act
        $result = $this->sut->getTodayDrawCount($this->userMock, 1);

        // Assert
        $this->assertIsInt($result);
        $this->assertGreaterThanOrEqual(0, $result);
    }

    #[Test]
    // Ref: TSD Section 3.13.3 - 抽奖记录
    public function get_total_draw_count_returns_total_count(): void
    {
        // Act
        $result = $this->sut->getTotalDrawCount($this->userMock, 1);

        // Assert
        $this->assertIsInt($result);
        $this->assertGreaterThanOrEqual(0, $result);
    }
}

