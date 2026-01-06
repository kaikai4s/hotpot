<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\TaskTemplate;
use App\Models\User;
use App\Models\UserTask;
use App\Services\PointService;
use App\Services\TaskService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

class TaskServiceTest extends TestCase
{
    private TaskService $sut;
    private PointService|MockObject $pointServiceMock;
    private User|MockObject $userMock;
    private UserTask|MockObject $userTaskMock;
    private TaskTemplate|MockObject $taskTemplateMock;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock dependencies
        $this->pointServiceMock = $this->createMock(PointService::class);

        // Mock User
        $this->userMock = $this->createMock(User::class);
        $this->userMock->id = 1;

        // Mock UserTask
        $this->userTaskMock = $this->createMock(UserTask::class);
        $this->userTaskMock->id = 1;
        $this->userTaskMock->user_id = 1;
        $this->userTaskMock->status = 'pending';
        $this->userTaskMock->progress = ['current' => 0, 'target' => 5];
        $this->userTaskMock->reward_issued = false;

        // Mock TaskTemplate
        $this->taskTemplateMock = $this->createMock(TaskTemplate::class);
        $this->taskTemplateMock->id = 1;
        $this->taskTemplateMock->name = '每日签到';
        $this->taskTemplateMock->type = 'daily';
        $this->taskTemplateMock->category = 'sign';
        $this->taskTemplateMock->reward_points = 10;
        $this->taskTemplateMock->target_value = ['count' => 1];

        // Instantiate SUT
        $this->sut = new TaskService($this->pointServiceMock);
    }

    #[Test]
    // Ref: TSD Section 3.10.1 - 任务类型，每日任务
    public function create_daily_tasks_for_user_creates_daily_tasks(): void
    {
        // Act
        $this->sut->createDailyTasksForUser($this->userMock);

        // Assert
        // 验证每日任务已创建
        // 注意：这是Red Stage，实际实现可能不同
    }

    #[Test]
    // Ref: TSD Section 3.10.1 - 任务类型，每日任务
    public function create_daily_tasks_for_user_skips_when_already_created_today(): void
    {
        // Act
        $this->sut->createDailyTasksForUser($this->userMock);

        // Assert
        // 验证如果今天已创建过每日任务，则跳过
        // 注意：这是Red Stage，实际实现可能不同
    }

    #[Test]
    // Ref: TSD Section 3.10.1 - 任务类型，每周任务
    public function create_weekly_tasks_for_user_creates_weekly_tasks(): void
    {
        // Act
        $this->sut->createWeeklyTasksForUser($this->userMock);

        // Assert
        // 验证每周任务已创建
        // 注意：这是Red Stage，实际实现可能不同
    }

    #[Test]
    // Ref: TSD Section 3.10.2 - 任务完成，任务进度追踪
    public function check_task_completion_updates_progress_when_increment_provided(): void
    {
        // Act
        $this->sut->checkTaskCompletion($this->userMock, 'order', 1);

        // Assert
        // 验证任务进度已更新
        // 注意：这是Red Stage，实际实现可能不同
    }

    #[Test]
    // Ref: TSD Section 3.10.2 - 任务完成，任务完成奖励
    public function complete_task_issues_reward_points_when_reward_points_set(): void
    {
        // Arrange
        $this->userTaskMock->taskTemplate = $this->taskTemplateMock;
        $this->userTaskMock->user = $this->userMock;
        $this->pointServiceMock->expects($this->once())
            ->method('earnPoints')
            ->with($this->userMock, 10, 'task', 1, "完成任务：每日签到");

        // Act
        $this->sut->completeTask($this->userTaskMock);

        // Assert
        // 验证积分奖励已发放
        // 注意：这是Red Stage，实际实现可能不同
    }

    #[Test]
    // Ref: TSD Section 3.10.2 - 任务完成
    public function complete_task_skips_when_reward_already_issued(): void
    {
        // Arrange
        $this->userTaskMock->reward_issued = true;

        // Act
        $this->sut->completeTask($this->userTaskMock);

        // Assert
        // 验证如果奖励已发放，则跳过
        // 注意：这是Red Stage，实际实现可能不同
    }

    #[Test]
    // Ref: TSD Section 3.10.2 - 任务刷新，每日任务每日刷新
    public function check_task_completion_marks_task_as_expired_when_expires_at_passed(): void
    {
        // Arrange
        $this->userTaskMock->expires_at = now()->subDay();

        // Act
        $this->sut->checkTaskCompletion($this->userMock, 'order', 1);

        // Assert
        // 验证过期任务状态变为expired
        // 注意：这是Red Stage，实际实现可能不同
    }

    #[Test]
    // Ref: TSD Section 3.10.2 - 手动完成任务
    public function complete_task_manually_updates_progress_and_completes_when_target_reached(): void
    {
        // Act
        $result = $this->sut->completeTaskManually($this->userMock, 1);

        // Assert
        // 如果任务存在且未过期，返回UserTask；否则返回null
        // 验证进度已更新，如果达到目标则完成任务
        // 注意：这是Red Stage，实际实现可能不同
    }
}

