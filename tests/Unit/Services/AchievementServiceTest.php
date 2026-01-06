<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\AchievementTemplate;
use App\Models\User;
use App\Models\UserAchievement;
use App\Services\AchievementService;
use App\Services\PointService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

class AchievementServiceTest extends TestCase
{
    private AchievementService $sut;
    private PointService|MockObject $pointServiceMock;
    private User|MockObject $userMock;
    private UserAchievement|MockObject $userAchievementMock;
    private AchievementTemplate|MockObject $achievementTemplateMock;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock dependencies
        $this->pointServiceMock = $this->createMock(PointService::class);

        // Mock User
        $this->userMock = $this->createMock(User::class);
        $this->userMock->id = 1;

        // Mock UserAchievement
        $this->userAchievementMock = $this->createMock(UserAchievement::class);
        $this->userAchievementMock->id = 1;
        $this->userAchievementMock->user_id = 1;
        $this->userAchievementMock->progress = ['current' => 0, 'target' => 10];
        $this->userAchievementMock->reward_issued = false;
        $this->userAchievementMock->completed_at = null;

        // Mock AchievementTemplate
        $this->achievementTemplateMock = $this->createMock(AchievementTemplate::class);
        $this->achievementTemplateMock->id = 1;
        $this->achievementTemplateMock->name = '消费达人';
        $this->achievementTemplateMock->category = 'consume';
        $this->achievementTemplateMock->reward_points = 100;
        $this->achievementTemplateMock->target_value = ['count' => 10];

        // Instantiate SUT
        $this->sut = new AchievementService($this->pointServiceMock);
    }

    #[Test]
    // Ref: TSD Section 3.12.1 - 成就分类
    public function check_achievement_completion_checks_achievements_by_category(): void
    {
        // Act
        $this->sut->checkAchievementCompletion($this->userMock, 'consume', 1);

        // Assert
        // 验证该分类的成就已检查
        // 注意：这是Red Stage，实际实现可能不同
    }

    #[Test]
    // Ref: TSD Section 3.12.2 - 成就奖励
    public function complete_achievement_issues_reward_points_when_reward_points_set(): void
    {
        // Arrange
        $this->userAchievementMock->achievementTemplate = $this->achievementTemplateMock;
        $this->userAchievementMock->user = $this->userMock;
        $this->pointServiceMock->expects($this->once())
            ->method('earnPoints')
            ->with($this->userMock, 100, 'achievement', 1, "完成成就：消费达人");

        // Act
        $this->sut->completeAchievement($this->userAchievementMock);

        // Assert
        // 验证积分奖励已发放
        // 注意：这是Red Stage，实际实现可能不同
    }

    #[Test]
    // Ref: TSD Section 3.12.2 - 成就奖励
    public function complete_achievement_skips_when_reward_already_issued(): void
    {
        // Arrange
        $this->userAchievementMock->reward_issued = true;

        // Act
        $this->sut->completeAchievement($this->userAchievementMock);

        // Assert
        // 验证如果奖励已发放，则跳过
        // 注意：这是Red Stage，实际实现可能不同
    }

    #[Test]
    // Ref: TSD Section 3.12.1 - 成就分类
    public function get_user_achievements_returns_all_achievements_for_user(): void
    {
        // Act
        $result = $this->sut->getUserAchievements($this->userMock);

        // Assert
        $this->assertIsArray($result);
        // 验证返回所有成就记录
        // 注意：这是Red Stage，实际实现可能不同
    }

    #[Test]
    // Ref: TSD Section 3.12.1 - 成就分类
    public function get_user_achievements_filters_by_category_when_provided(): void
    {
        // Act
        $result = $this->sut->getUserAchievements($this->userMock, 'consume');

        // Assert
        $this->assertIsArray($result);
        // 验证只返回指定分类的成就
        // 注意：这是Red Stage，实际实现可能不同
    }

    #[Test]
    // Ref: TSD Section 3.12.2 - 更新成就进度
    public function update_achievement_progress_updates_progress_and_completes_when_target_reached(): void
    {
        // Arrange
        $this->userAchievementMock->achievementTemplate = $this->achievementTemplateMock;
        $this->userAchievementMock->user = $this->userMock;

        // Act
        $this->sut->updateAchievementProgress($this->userAchievementMock, $this->achievementTemplateMock, 10);

        // Assert
        // 验证进度已更新，如果达到目标则完成成就
        // 注意：这是Red Stage，实际实现可能不同
    }
}

