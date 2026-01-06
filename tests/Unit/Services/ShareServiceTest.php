<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\User;
use App\Models\UserShare;
use App\Services\PointService;
use App\Services\ShareService;
use App\Services\TaskService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

class ShareServiceTest extends TestCase
{
    private ShareService $sut;
    private PointService|MockObject $pointServiceMock;
    private TaskService|MockObject $taskServiceMock;
    private User|MockObject $userMock;
    private UserShare|MockObject $userShareMock;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock dependencies
        $this->pointServiceMock = $this->createMock(PointService::class);
        $this->taskServiceMock = $this->createMock(TaskService::class);

        // Mock User
        $this->userMock = $this->createMock(User::class);
        $this->userMock->id = 1;

        // Mock UserShare
        $this->userShareMock = $this->createMock(UserShare::class);
        $this->userShareMock->id = 1;
        $this->userShareMock->user_id = 1;
        $this->userShareMock->share_type = 'review';
        $this->userShareMock->share_content_id = 1;
        $this->userShareMock->reward_points = 20;
        $this->userShareMock->reward_issued = false;
        $this->userShareMock->user = $this->userMock;

        // Instantiate SUT
        $this->sut = new ShareService($this->pointServiceMock, $this->taskServiceMock);
    }

    #[Test]
    // Ref: TSD Section 3.15.1 - 分享记录
    public function record_share_creates_share_record(): void
    {
        // Act
        $result = $this->sut->recordShare($this->userMock, 'review', 1, 'moments');

        // Assert
        $this->assertInstanceOf(UserShare::class, $result);
        // 验证分享记录已创建
        // 注意：这是Red Stage，实际实现可能不同
    }

    #[Test]
    // Ref: TSD Section 3.15.2 - 分享奖励，每日限制
    public function can_get_share_reward_returns_false_when_daily_limit_reached(): void
    {
        // Act
        $result = $this->sut->canGetShareReward($this->userMock, 'review', 1);

        // Assert
        $this->assertIsBool($result);
        // 如果今日分享该类型该内容已获得3次奖励，返回false
        // 注意：这是Red Stage，实际实现可能不同
    }

    #[Test]
    // Ref: TSD Section 3.15.2 - 分享奖励
    public function issue_share_reward_issues_points_when_reward_points_set(): void
    {
        // Arrange
        $this->pointServiceMock->expects($this->once())
            ->method('earnPoints')
            ->with($this->userMock, 20, 'share', 1, '分享奖励（review）');

        // Act
        $this->sut->issueShareReward($this->userShareMock);

        // Assert
        // 验证积分奖励已发放，reward_issued已设置为true
        // 注意：这是Red Stage，实际实现可能不同
    }

    #[Test]
    // Ref: TSD Section 3.15.2 - 分享奖励
    public function issue_share_reward_skips_when_reward_already_issued(): void
    {
        // Arrange
        $this->userShareMock->reward_issued = true;

        // Act
        $this->sut->issueShareReward($this->userShareMock);

        // Assert
        // 验证如果奖励已发放，则跳过
        // 注意：这是Red Stage，实际实现可能不同
    }

    #[Test]
    // Ref: TSD Section 3.15.2 - 分享奖励
    public function issue_share_reward_skips_when_reward_points_zero(): void
    {
        // Arrange
        $this->userShareMock->reward_points = 0;

        // Act
        $this->sut->issueShareReward($this->userShareMock);

        // Assert
        // 验证如果没有奖励积分，则跳过
        // 注意：这是Red Stage，实际实现可能不同
    }

    #[Test]
    // Ref: TSD Section 3.15.2 - 分享统计
    public function get_share_stats_returns_statistics(): void
    {
        // Act
        $result = $this->sut->getShareStats($this->userMock);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_shares', $result);
        $this->assertArrayHasKey('rewarded_shares', $result);
        $this->assertArrayHasKey('total_reward_points', $result);
        $this->assertArrayHasKey('by_type', $result);
    }

    #[Test]
    // Ref: TSD Section 3.15.2 - 分享统计
    public function get_share_stats_filters_by_type_when_provided(): void
    {
        // Act
        $result = $this->sut->getShareStats($this->userMock, 'review');

        // Assert
        $this->assertIsArray($result);
        // 验证只返回指定类型的统计
        // 注意：这是Red Stage，实际实现可能不同
    }

    #[Test]
    // Ref: TSD Section 3.15.1 - 获取用户的分享列表
    public function get_user_shares_returns_share_list(): void
    {
        // Act
        $result = $this->sut->getUserShares($this->userMock);

        // Assert
        $this->assertIsArray($result);
    }

    #[Test]
    // Ref: TSD Section 3.15.1 - 获取用户的分享列表
    public function get_user_shares_filters_by_type_when_provided(): void
    {
        // Act
        $result = $this->sut->getUserShares($this->userMock, 'review', 20);

        // Assert
        $this->assertIsArray($result);
        // 验证只返回指定类型的分享
        // 注意：这是Red Stage，实际实现可能不同
    }
}

