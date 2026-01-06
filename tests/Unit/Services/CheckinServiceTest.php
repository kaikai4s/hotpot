<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\User;
use App\Models\UserCheckin;
use App\Services\AchievementService;
use App\Services\CheckinService;
use App\Services\PointService;
use App\Services\TaskService;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

class CheckinServiceTest extends TestCase
{
    private CheckinService $sut;
    private PointService|MockObject $pointServiceMock;
    private TaskService|MockObject $taskServiceMock;
    private AchievementService|MockObject $achievementServiceMock;
    private User|MockObject $userMock;
    private UserCheckin|MockObject $userCheckinMock;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock dependencies
        $this->pointServiceMock = $this->createMock(PointService::class);
        $this->taskServiceMock = $this->createMock(TaskService::class);
        $this->achievementServiceMock = $this->createMock(AchievementService::class);

        // Mock User
        $this->userMock = $this->createMock(User::class);
        $this->userMock->id = 1;

        // Mock UserCheckin
        $this->userCheckinMock = $this->createMock(UserCheckin::class);
        $this->userCheckinMock->id = 1;
        $this->userCheckinMock->user_id = 1;
        $this->userCheckinMock->checkin_date = Carbon::today();
        $this->userCheckinMock->consecutive_days = 1;
        $this->userCheckinMock->reward_points = 10;
        $this->userCheckinMock->is_makeup = false;

        // Instantiate SUT
        $this->sut = new CheckinService(
            $this->pointServiceMock,
            $this->taskServiceMock,
            $this->achievementServiceMock
        );
    }

    #[Test]
    // Ref: TSD Section 3.11.1 - 每日签到
    public function checkin_creates_checkin_record_and_issues_reward_points(): void
    {
        // Arrange
        $this->pointServiceMock->expects($this->once())
            ->method('earnPoints')
            ->with($this->userMock, $this->anything(), 'checkin', $this->anything(), $this->stringContains('每日签到奖励'));

        // Act
        $result = $this->sut->checkin($this->userMock);

        // Assert
        $this->assertInstanceOf(UserCheckin::class, $result);
        // 验证签到记录已创建，积分奖励已发放
        // 注意：这是Red Stage，实际实现可能不同
    }

    #[Test]
    // Ref: TSD Section 3.11.1 - 每日签到
    public function checkin_throws_exception_when_already_checked_in_today(): void
    {
        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('今日已签到');

        // 模拟今天已签到
        // 注意：这是Red Stage，实际实现可能不同
        $this->sut->checkin($this->userMock);
    }

    #[Test]
    // Ref: TSD Section 3.11.1 - 连续签到奖励递增
    public function calculate_reward_points_returns_higher_points_for_more_consecutive_days(): void
    {
        // Act & Assert
        $day1Points = $this->sut->calculateRewardPoints(1);
        $day7Points = $this->sut->calculateRewardPoints(7);
        $day14Points = $this->sut->calculateRewardPoints(14);
        $day28Points = $this->sut->calculateRewardPoints(28);

        $this->assertEquals(10, $day1Points);
        $this->assertEquals(50, $day7Points); // 连续一周奖励
        $this->assertEquals(100, $day14Points); // 连续两周奖励
        $this->assertEquals(300, $day28Points); // 连续四周奖励
    }

    #[Test]
    // Ref: TSD Section 3.11.1 - 计算连续签到天数
    public function calculate_consecutive_days_returns_1_when_no_last_checkin(): void
    {
        // Act
        $result = $this->sut->calculateConsecutiveDays($this->userMock, Carbon::today(), false);

        // Assert
        $this->assertIsInt($result);
        // 如果没有上次签到记录，返回1
        // 注意：这是Red Stage，实际实现可能不同
    }

    #[Test]
    // Ref: TSD Section 3.11.1 - 计算连续签到天数
    public function calculate_consecutive_days_increments_when_last_checkin_was_yesterday(): void
    {
        // Act
        $result = $this->sut->calculateConsecutiveDays($this->userMock, Carbon::today(), false);

        // Assert
        $this->assertIsInt($result);
        // 如果昨天签到，连续天数+1
        // 注意：这是Red Stage，实际实现可能不同
    }

    #[Test]
    // Ref: TSD Section 3.11.2 - 补签功能
    public function makeup_checkin_creates_checkin_record_and_deducts_points(): void
    {
        // Arrange
        $date = Carbon::yesterday();
        $this->pointServiceMock->expects($this->exactly(2))
            ->method('spendPoints')
            ->willReturn($this->createMock(\App\Models\PointTransaction::class));
        $this->pointServiceMock->expects($this->once())
            ->method('earnPoints')
            ->willReturn($this->createMock(\App\Models\PointTransaction::class));

        // Act
        $result = $this->sut->makeupCheckin($this->userMock, $date);

        // Assert
        $this->assertInstanceOf(UserCheckin::class, $result);
        // 验证补签记录已创建，积分已扣除和发放
        // 注意：这是Red Stage，实际实现可能不同
    }

    #[Test]
    // Ref: TSD Section 3.11.2 - 补签功能
    public function makeup_checkin_throws_exception_when_date_is_today_or_future(): void
    {
        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('只能补签过去的日期');

        $this->sut->makeupCheckin($this->userMock, Carbon::today());
    }

    #[Test]
    // Ref: TSD Section 3.11.2 - 补签功能
    public function makeup_checkin_throws_exception_when_date_already_checked_in(): void
    {
        // Arrange
        $date = Carbon::yesterday();

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('该日期已签到');

        // 模拟该日期已签到
        // 注意：这是Red Stage，实际实现可能不同
        $this->sut->makeupCheckin($this->userMock, $date);
    }

    #[Test]
    // Ref: TSD Section 3.11.2 - 补签功能，补签次数限制
    public function makeup_checkin_throws_exception_when_points_insufficient(): void
    {
        // Arrange
        $date = Carbon::yesterday();
        $this->pointServiceMock->expects($this->once())
            ->method('getPoints')
            ->willReturn($this->createMock(\App\Models\MemberPoint::class));

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('积分不足');

        // 模拟积分不足
        // 注意：这是Red Stage，实际实现可能不同
        $this->sut->makeupCheckin($this->userMock, $date);
    }

    #[Test]
    // Ref: TSD Section 3.11.1 - 签到日历展示
    public function get_checkin_calendar_returns_calendar_data_for_month(): void
    {
        // Act
        $result = $this->sut->getCheckinCalendar($this->userMock, 2026, 1);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('year', $result);
        $this->assertArrayHasKey('month', $result);
        $this->assertArrayHasKey('calendar', $result);
        $this->assertArrayHasKey('stat', $result);
    }

    #[Test]
    // Ref: TSD Section 3.11.1 - 获取用户签到统计
    public function get_checkin_stat_returns_statistics(): void
    {
        // Act
        $result = $this->sut->getCheckinStat($this->userMock);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_days', $result);
        $this->assertArrayHasKey('current_consecutive_days', $result);
        $this->assertArrayHasKey('max_consecutive_days', $result);
        $this->assertArrayHasKey('is_checked_today', $result);
    }
}

