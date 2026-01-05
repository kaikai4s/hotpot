<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Models\UserTask;
use App\Models\TaskTemplate;
use App\Models\UserCoupon;
use App\Services\PointService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TaskService
{
    public function __construct(
        private PointService $pointService
    ) {
    }

    /**
     * 为用户创建每日任务
     */
    public function createDailyTasksForUser(User $user): void
    {
        $today = Carbon::today();
        
        // 检查今天是否已经创建过每日任务
        $existingTasks = UserTask::where('user_id', $user->id)
            ->whereHas('taskTemplate', function ($query) {
                $query->where('type', 'daily');
            })
            ->whereDate('created_at', $today)
            ->exists();

        if ($existingTasks) {
            return; // 今天已经创建过每日任务
        }

        // 获取所有启用的每日任务模板
        $templates = TaskTemplate::where('type', 'daily')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        foreach ($templates as $template) {
            // 计算过期时间（今天23:59:59）
            $expiresAt = $today->copy()->endOfDay();

            UserTask::create([
                'user_id' => $user->id,
                'task_template_id' => $template->id,
                'status' => 'pending',
                'progress' => ['current' => 0, 'target' => $this->getTargetValue($template)],
                'expires_at' => $expiresAt,
            ]);
        }
    }

    /**
     * 为用户创建每周任务
     */
    public function createWeeklyTasksForUser(User $user): void
    {
        $monday = Carbon::now()->startOfWeek();
        
        // 检查本周是否已经创建过每周任务
        $existingTasks = UserTask::where('user_id', $user->id)
            ->whereHas('taskTemplate', function ($query) {
                $query->where('type', 'weekly');
            })
            ->where('created_at', '>=', $monday)
            ->exists();

        if ($existingTasks) {
            return; // 本周已经创建过每周任务
        }

        // 获取所有启用的每周任务模板
        $templates = TaskTemplate::where('type', 'weekly')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        foreach ($templates as $template) {
            // 计算过期时间（本周日23:59:59）
            $expiresAt = $monday->copy()->endOfWeek();

            UserTask::create([
                'user_id' => $user->id,
                'task_template_id' => $template->id,
                'status' => 'pending',
                'progress' => ['current' => 0, 'target' => $this->getTargetValue($template)],
                'expires_at' => $expiresAt,
            ]);
        }
    }

    /**
     * 检测任务完成
     */
    public function checkTaskCompletion(User $user, string $category, int $increment = 1, ?array $extraData = null): void
    {
        // 获取用户进行中的任务（该分类的）
        $tasks = UserTask::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'in_progress'])
            ->whereHas('taskTemplate', function ($query) use ($category) {
                $query->where('category', $category)
                    ->where('is_active', true);
            })
            ->get();

        foreach ($tasks as $task) {
            // 成就任务没有过期时间，其他任务需要检查过期时间
            if ($task->taskTemplate->type !== 'achievement' && $task->expires_at && $task->expires_at->isPast()) {
                $task->update(['status' => 'expired']);
                continue;
            }

            $progress = $task->progress ?? ['current' => 0, 'target' => 0];
            $targetValue = $task->taskTemplate->target_value ?? [];
            
            // 根据target_value类型决定如何更新进度
            if (isset($targetValue['amount'])) {
                // 累计金额类型：需要重新计算总金额
                $current = $this->calculateCurrentProgress($user, $task->taskTemplate);
            } else {
                // 计数类型：累加increment
                $current = ($progress['current'] ?? 0) + $increment;
            }
            
            $target = $this->getTargetValue($task->taskTemplate, $extraData);

            // 更新进度
            $task->update([
                'status' => $current >= $target ? 'completed' : 'in_progress',
                'progress' => ['current' => $current, 'target' => $target],
            ]);

            // 如果完成，发放奖励
            if ($current >= $target && $task->status === 'completed') {
                $this->completeTask($task);
            }
        }

        // 检查并创建成就任务（如果还没有创建）
        $this->checkAndCreateAchievementTasks($user, $category);
    }

    /**
     * 计算当前进度（用于成就任务）
     */
    private function calculateCurrentProgress(User $user, TaskTemplate $template): int
    {
        $targetValue = $template->target_value ?? [];

        if ($template->category === 'order') {
            if (isset($targetValue['count'])) {
                // 累计订单数量
                return $user->orders()->whereIn('status', ['paid', 'pending_review', 'completed'])->count();
            } elseif (isset($targetValue['amount'])) {
                // 累计消费金额
                return (int) $user->orders()->whereIn('status', ['paid', 'pending_review', 'completed'])->sum('total_amount');
            }
        } elseif ($template->category === 'review') {
            return $user->reviews()->where('status', 'approved')->count();
        } elseif ($template->category === 'invite') {
            return $user->invitations()->where('status', '!=', 'pending')->count();
        }

        return 0;
    }

    /**
     * 检查并创建成就任务
     */
    private function checkAndCreateAchievementTasks(User $user, string $category): void
    {
        // 获取该分类的成就任务模板
        $templates = TaskTemplate::where('type', 'achievement')
            ->where('category', $category)
            ->where('is_active', true)
            ->get();

        foreach ($templates as $template) {
            // 检查是否已经创建过该成就任务
            $existingTask = UserTask::where('user_id', $user->id)
                ->where('task_template_id', $template->id)
                ->exists();

            if (!$existingTask) {
                // 创建成就任务（成就任务没有过期时间）
                UserTask::create([
                    'user_id' => $user->id,
                    'task_template_id' => $template->id,
                    'status' => 'pending',
                    'progress' => $this->getInitialProgress($user, $template),
                    'expires_at' => null, // 成就任务永不过期
                ]);
            }
        }
    }

    /**
     * 获取初始进度（用于成就任务）
     */
    private function getInitialProgress(User $user, TaskTemplate $template): array
    {
        $targetValue = $template->target_value ?? [];
        $current = 0;

        // 根据任务分类和target_value类型计算当前进度
        if ($template->category === 'order') {
            if (isset($targetValue['count'])) {
                // 累计订单数量
                $current = $user->orders()->whereIn('status', ['paid', 'pending_review', 'completed'])->count();
            } elseif (isset($targetValue['amount'])) {
                // 累计消费金额
                $current = (int) $user->orders()->whereIn('status', ['paid', 'pending_review', 'completed'])->sum('total_amount');
            }
        } elseif ($template->category === 'review') {
            $current = $user->reviews()->where('status', 'approved')->count();
        } elseif ($template->category === 'invite') {
            $current = $user->invitations()->where('status', '!=', 'pending')->count();
        }

        return [
            'current' => $current,
            'target' => $this->getTargetValue($template),
        ];
    }

    /**
     * 完成任务并发放奖励
     */
    public function completeTask(UserTask $userTask): void
    {
        if ($userTask->reward_issued) {
            return; // 奖励已发放
        }

        DB::transaction(function () use ($userTask) {
            $template = $userTask->taskTemplate;
            $user = $userTask->user;

            // 发放积分奖励
            if ($template->reward_points > 0) {
                $this->pointService->earnPoints(
                    $user,
                    $template->reward_points,
                    'task',
                    $userTask->id,
                    "完成任务：{$template->name}"
                );
            }

            // 发放优惠券奖励
            if ($template->reward_coupon_id) {
                $coupon = $template->rewardCoupon;
                if ($coupon && $coupon->is_active && $coupon->stock > 0) {
                    UserCoupon::create([
                        'user_id' => $user->id,
                        'coupon_id' => $coupon->id,
                        'status' => 'unused',
                        'expires_at' => now()->addDays(30),
                    ]);

                    // 减少优惠券库存
                    $coupon->decrement('stock');
                }
            }

            // 标记奖励已发放
            $userTask->update([
                'completed_at' => now(),
                'reward_issued' => true,
            ]);

            Log::info('任务完成，奖励已发放', [
                'user_task_id' => $userTask->id,
                'user_id' => $user->id,
                'task_template_id' => $template->id,
                'reward_points' => $template->reward_points,
            ]);
        });
    }

    /**
     * 获取用户的任务列表
     */
    public function getUserTasks(User $user, ?string $type = null): array
    {
        $query = UserTask::where('user_id', $user->id)
            ->with('taskTemplate')
            ->orderBy('created_at', 'desc');

        if ($type) {
            $query->whereHas('taskTemplate', function ($q) use ($type) {
                $q->where('type', $type);
            });
        }

        $tasks = $query->get();

        // 检查并处理过期任务
        foreach ($tasks as $task) {
            if ($task->expires_at && $task->expires_at->isPast() && $task->status !== 'completed') {
                $task->update(['status' => 'expired']);
            }
        }

        return $tasks->toArray();
    }

    /**
     * 获取目标值
     */
    private function getTargetValue(TaskTemplate $template, ?array $extraData = null): int
    {
        $targetValue = $template->target_value ?? [];
        
        // 支持count和amount两种类型
        if (isset($targetValue['count'])) {
            return (int) $targetValue['count'];
        } elseif (isset($targetValue['amount'])) {
            return (int) $targetValue['amount'];
        }
        
        return 1;
    }

    /**
     * 手动完成任务（用于签到、分享等需要用户主动完成的任务）
     */
    public function completeTaskManually(User $user, int $taskTemplateId): ?UserTask
    {
        $task = UserTask::where('user_id', $user->id)
            ->where('task_template_id', $taskTemplateId)
            ->whereIn('status', ['pending', 'in_progress'])
            ->where('expires_at', '>', now())
            ->first();

        if (!$task) {
            return null;
        }

        $progress = $task->progress ?? ['current' => 0, 'target' => 0];
        $current = ($progress['current'] ?? 0) + 1;
        $target = $progress['target'] ?? $this->getTargetValue($task->taskTemplate);

        $task->update([
            'status' => $current >= $target ? 'completed' : 'in_progress',
            'progress' => ['current' => $current, 'target' => $target],
        ]);

        if ($current >= $target && $task->status === 'completed') {
            $this->completeTask($task);
        }

        return $task;
    }
}

