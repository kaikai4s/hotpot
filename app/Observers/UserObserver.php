<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Observers;

use App\Models\User;
use App\Services\InvitationService;
use App\Services\TaskService;
use App\Services\AchievementService;
use Illuminate\Support\Facades\Log;

class UserObserver
{
    public function __construct(
        private InvitationService $invitationService,
        private TaskService $taskService,
        private AchievementService $achievementService
    ) {
    }

    /**
     * 用户创建时生成邀请码
     */
    public function created(User $user): void
    {
        try {
            // 为新用户生成邀请码
            $this->invitationService->generateInviteCode($user);
            
            Log::info('新用户注册，已生成邀请码', [
                'user_id' => $user->id,
                'invite_code' => $user->invite_code,
            ]);
        } catch (\Exception $e) {
            Log::error('生成用户邀请码失败', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }

        try {
            // 为新用户创建初始任务（每日任务和每周任务）
            $this->taskService->createDailyTasksForUser($user);
            $this->taskService->createWeeklyTasksForUser($user);
            
            Log::info('新用户注册，已创建初始任务', [
                'user_id' => $user->id,
            ]);
        } catch (\Exception $e) {
            Log::error('创建用户初始任务失败', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

