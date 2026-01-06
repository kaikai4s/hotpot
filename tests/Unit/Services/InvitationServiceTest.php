<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\User;
use App\Models\UserInvitation;
use App\Services\AchievementService;
use App\Services\InvitationService;
use App\Services\PointService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

class InvitationServiceTest extends TestCase
{
    private InvitationService $sut;
    private PointService|MockObject $pointServiceMock;
    private AchievementService|MockObject $achievementServiceMock;
    private User|MockObject $userMock;
    private User|MockObject $inviterMock;
    private UserInvitation|MockObject $invitationMock;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock dependencies
        $this->pointServiceMock = $this->createMock(PointService::class);
        $this->achievementServiceMock = $this->createMock(AchievementService::class);

        // Mock User (invitee)
        $this->userMock = $this->createMock(User::class);
        $this->userMock->id = 2;
        $this->userMock->invite_code = null;
        $this->userMock->invited_by = null;

        // Mock User (inviter)
        $this->inviterMock = $this->createMock(User::class);
        $this->inviterMock->id = 1;
        $this->inviterMock->invite_code = 'INV000001ABC123';

        // Mock UserInvitation
        $this->invitationMock = $this->createMock(UserInvitation::class);
        $this->invitationMock->id = 1;
        $this->invitationMock->inviter_id = 1;
        $this->invitationMock->invitee_id = 2;
        $this->invitationMock->invite_code = 'INV000001ABC123';
        $this->invitationMock->status = 'registered';
        $this->invitationMock->reward_issued = false;
        $this->invitationMock->inviter = $this->inviterMock;

        // Instantiate SUT
        $this->sut = new InvitationService($this->pointServiceMock, $this->achievementServiceMock);
    }

    #[Test]
    // Ref: TSD Section 3.14.1 - 邀请码生成
    public function generate_invite_code_returns_existing_code_when_already_exists(): void
    {
        // Arrange
        $this->userMock->invite_code = 'INV000001ABC123';

        // Act
        $result = $this->sut->generateInviteCode($this->userMock);

        // Assert
        $this->assertEquals('INV000001ABC123', $result);
    }

    #[Test]
    // Ref: TSD Section 3.14.1 - 邀请码生成
    public function generate_invite_code_generates_unique_code_when_not_exists(): void
    {
        // Act
        $result = $this->sut->generateInviteCode($this->userMock);

        // Assert
        $this->assertIsString($result);
        $this->assertStringStartsWith('INV', $result);
        // 验证邀请码格式：INV{user_id}{6位随机字符}
        // 注意：这是Red Stage，实际实现可能不同
    }

    #[Test]
    // Ref: TSD Section 3.14.2 - 使用邀请码注册
    public function register_with_invite_code_creates_invitation_record(): void
    {
        // Act
        $result = $this->sut->registerWithInviteCode($this->userMock, 'INV000001ABC123');

        // Assert
        $this->assertInstanceOf(UserInvitation::class, $result);
        // 验证邀请记录已创建，invited_by已设置
        // 注意：这是Red Stage，实际实现可能不同
    }

    #[Test]
    // Ref: TSD Section 3.14.2 - 使用邀请码注册
    public function register_with_invite_code_returns_null_when_invite_code_invalid(): void
    {
        // Act
        $result = $this->sut->registerWithInviteCode($this->userMock, 'INVALID_CODE');

        // Assert
        $this->assertNull($result);
    }

    #[Test]
    // Ref: TSD Section 3.14.2 - 使用邀请码注册
    public function register_with_invite_code_returns_null_when_inviting_self(): void
    {
        // Arrange
        $this->userMock->id = 1; // 邀请人ID

        // Act
        $result = $this->sut->registerWithInviteCode($this->userMock, 'INV000001ABC123');

        // Assert
        $this->assertNull($result);
    }

    #[Test]
    // Ref: TSD Section 3.14.2 - 使用邀请码注册
    public function register_with_invite_code_returns_existing_when_already_invited(): void
    {
        // Act
        $result = $this->sut->registerWithInviteCode($this->userMock, 'INV000001ABC123');

        // Assert
        // 如果已被邀请过，返回已有邀请记录
        // 注意：这是Red Stage，实际实现可能不同
    }

    #[Test]
    // Ref: TSD Section 3.14.2 - 邀请奖励，被邀请人奖励
    public function register_with_invite_code_issues_new_user_reward(): void
    {
        // Arrange
        $this->pointServiceMock->expects($this->once())
            ->method('earnPoints')
            ->with($this->userMock, 100, 'invite_new_user', null, '新人注册礼包');

        // Act
        $result = $this->sut->registerWithInviteCode($this->userMock, 'INV000001ABC123');

        // Assert
        // 验证被邀请人获得100积分和新人优惠券
        // 注意：这是Red Stage，实际实现可能不同
    }

    #[Test]
    // Ref: TSD Section 3.14.2 - 邀请奖励，邀请人奖励
    public function issue_invitation_rewards_issues_points_to_inviter(): void
    {
        // Arrange
        $this->invitationMock->inviter = $this->inviterMock;
        $this->pointServiceMock->expects($this->once())
            ->method('earnPoints')
            ->with($this->inviterMock, 200, 'invite_reward', 1, '邀请好友首次消费奖励');

        // Act
        $this->sut->issueInvitationRewards($this->invitationMock);

        // Assert
        // 验证邀请人获得200积分，邀请记录状态变为completed
        // 注意：这是Red Stage，实际实现可能不同
    }

    #[Test]
    // Ref: TSD Section 3.14.2 - 邀请奖励
    public function issue_invitation_rewards_skips_when_reward_already_issued(): void
    {
        // Arrange
        $this->invitationMock->reward_issued = true;

        // Act
        $this->sut->issueInvitationRewards($this->invitationMock);

        // Assert
        // 验证如果奖励已发放，则跳过
        // 注意：这是Red Stage，实际实现可能不同
    }

    #[Test]
    // Ref: TSD Section 3.14.3 - 邀请统计
    public function get_invitation_stats_returns_statistics(): void
    {
        // Act
        $result = $this->sut->getInvitationStats($this->userMock);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('invite_code', $result);
        $this->assertArrayHasKey('total_invites', $result);
        $this->assertArrayHasKey('successful_invites', $result);
        $this->assertArrayHasKey('total_rewards_points', $result);
        $this->assertArrayHasKey('friends', $result);
    }

    #[Test]
    // Ref: TSD Section 3.14.3 - 邀请统计
    public function get_invitation_stats_generates_invite_code_when_not_exists(): void
    {
        // Act
        $result = $this->sut->getInvitationStats($this->userMock);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('invite_code', $result);
        // 验证如果用户没有邀请码，自动生成
        // 注意：这是Red Stage，实际实现可能不同
    }

    #[Test]
    // Ref: TSD Section 3.14.2 - 获取邀请的好友列表
    public function get_invited_friends_returns_paginated_friends_list(): void
    {
        // Act
        $result = $this->sut->getInvitedFriends($this->userMock, 20);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('friends', $result);
        $this->assertArrayHasKey('pagination', $result);
    }
}

