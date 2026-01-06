<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Models\UserCheckin;
use App\Models\UserCheckinStat;
use App\Services\PointService;
use App\Services\TaskService;
use App\Services\AchievementService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckinService
{
    public function __construct(
        private PointService $pointService,
        private TaskService $taskService,
        private AchievementService $achievementService
    ) {
    }

    /**
     * 每日签到
     */
    public function checkin(User $user, bool $isMakeup = false): UserCheckin
    {
        $today = Carbon::today();

        // 检查今天是否已经签到
        $existingCheckin = UserCheckin::where('user_id', $user->id)
            ->whereDate('checkin_date', $today)
            ->first();

        if ($existingCheckin && !$isMakeup) {
            throw new \Exception('今日已签到，请勿重复签到');
        }

        return DB::transaction(function () use ($user, $today, $isMakeup) {
            // 计算连续签到天数
            $consecutiveDays = $this->calculateConsecutiveDays($user, $today, $isMakeup);
            
            // 计算奖励积分
            $rewardPoints = $this->calculateRewardPoints($consecutiveDays);

            // 创建签到记录
            $checkin = UserCheckin::create([
                'user_id' => $user->id,
                'checkin_date' => $today,
                'consecutive_days' => $consecutiveDays,
                'reward_points' => $rewardPoints,
                'is_makeup' => $isMakeup,
            ]);

            // 发放积分奖励
            if ($rewardPoints > 0) {
                $this->pointService->earnPoints(
                    $user,
                    $rewardPoints,
                    'checkin',
                    $checkin->id,
                    "每日签到奖励（连续{$consecutiveDays}天）"
                );
            }

            // 更新签到统计
            $this->updateCheckinStat($user, $today, $consecutiveDays, $isMakeup);

            // 检测任务完成（签到任务）
            try {
                $this->taskService->completeTaskManually($user, $this->getCheckinTaskTemplateId());
            } catch (\Exception $e) {
                Log::warning('签到任务完成检测失败', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }

            // 检测成就完成（签到类成就）
            try {
                $this->achievementService->checkAchievementCompletion($user, 'checkin', 1);
            } catch (\Exception $e) {
                Log::warning('签到成就完成检测失败', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }

            Log::info('用户签到成功', [
                'user_id' => $user->id,
                'checkin_date' => $today->toDateString(),
                'consecutive_days' => $consecutiveDays,
                'reward_points' => $rewardPoints,
                'is_makeup' => $isMakeup,
            ]);

            return $checkin;
        });
    }

    /**
     * 计算连续签到天数
     */
    public function calculateConsecutiveDays(User $user, Carbon $checkinDate, bool $isMakeup = false): int
    {
        $stat = $this->getOrCreateStat($user);

        // 如果是补签，不更新连续天数
        if ($isMakeup) {
            return $stat->current_consecutive_days;
        }

        $lastCheckinDate = $stat->last_checkin_date;

        // 如果没有上次签到记录，从1开始
        if (!$lastCheckinDate) {
            return 1;
        }

        $daysDiff = $lastCheckinDate->diffInDays($checkinDate);

        // 如果昨天签到，连续天数+1
        if ($daysDiff === 1) {
            return $stat->current_consecutive_days + 1;
        }

        // 如果间隔超过1天，重新开始计数
        return 1;
    }

    /**
     * 计算签到奖励积分
     * 
     * 奖励规则：
     * - 第1天：10积分
     * - 第2-3天：15积分/天
     * - 第4-6天：20积分/天
     * - 第7天：50积分（连续一周奖励）
     * - 第8-13天：25积分/天
     * - 第14天：100积分（连续两周奖励）
     * - 第15-20天：30积分/天
     * - 第21天：200积分（连续三周奖励）
     * - 第22-27天：35积分/天
     * - 第28天：300积分（连续四周奖励）
     * - 第29天及以上：40积分/天
     */
    public function calculateRewardPoints(int $consecutiveDays): int
    {
        if ($consecutiveDays === 1) {
            return 10;
        } elseif ($consecutiveDays >= 2 && $consecutiveDays <= 3) {
            return 15;
        } elseif ($consecutiveDays >= 4 && $consecutiveDays <= 6) {
            return 20;
        } elseif ($consecutiveDays === 7) {
            return 50; // 连续一周奖励
        } elseif ($consecutiveDays >= 8 && $consecutiveDays <= 13) {
            return 25;
        } elseif ($consecutiveDays === 14) {
            return 100; // 连续两周奖励
        } elseif ($consecutiveDays >= 15 && $consecutiveDays <= 20) {
            return 30;
        } elseif ($consecutiveDays === 21) {
            return 200; // 连续三周奖励
        } elseif ($consecutiveDays >= 22 && $consecutiveDays <= 27) {
            return 35;
        } elseif ($consecutiveDays === 28) {
            return 300; // 连续四周奖励
        } else {
            return 40; // 第29天及以上
        }
    }

    /**
     * 更新签到统计
     */
    private function updateCheckinStat(User $user, Carbon $checkinDate, int $consecutiveDays, bool $isMakeup): void
    {
        $stat = $this->getOrCreateStat($user);

        $stat->total_days = $stat->total_days + 1;
        $stat->current_consecutive_days = $consecutiveDays;
        $stat->last_checkin_date = $checkinDate;

        // 更新最大连续天数
        if ($consecutiveDays > $stat->max_consecutive_days) {
            $stat->max_consecutive_days = $consecutiveDays;
        }

        // 如果是补签，增加补签次数
        if ($isMakeup) {
            $stat->makeup_count = ($stat->makeup_count ?? 0) + 1;
        }

        $stat->save();
    }

    /**
     * 获取或创建签到统计
     */
    private function getOrCreateStat(User $user): UserCheckinStat
    {
        $startTime = microtime(true);
        
        $stat = UserCheckinStat::firstOrCreate(
            ['user_id' => $user->id],
            [
                'total_days' => 0,
                'max_consecutive_days' => 0,
                'current_consecutive_days' => 0,
                'last_checkin_date' => null,
                'makeup_count' => 0,
            ]
        );
        
        $queryTime = microtime(true) - $startTime;
        
        if ($queryTime > 0.5) {
            \Illuminate\Support\Facades\Log::warning('【签到统计-获取或创建耗时过长】', [
                'user_id' => $user->id,
                'was_recently_created' => $stat->wasRecentlyCreated ? 'yes' : 'no',
                'query_time' => round($queryTime, 3),
            ]);
        }
        
        return $stat;
    }

    /**
     * 获取签到日历数据
     */
    public function getCheckinCalendar(User $user, int $year, int $month): array
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        // 获取该月的所有签到记录
        $checkins = UserCheckin::where('user_id', $user->id)
            ->whereBetween('checkin_date', [$startDate, $endDate])
            ->get()
            ->keyBy(function ($checkin) {
                return $checkin->checkin_date->format('Y-m-d');
            });

        // 获取统计信息
        $stat = $this->getOrCreateStat($user);

        $calendar = [];
        $currentDate = $startDate->copy();

        while ($currentDate->lte($endDate)) {
            $dateKey = $currentDate->format('Y-m-d');
            $checkin = $checkins->get($dateKey);

            $calendar[] = [
                'date' => $dateKey,
                'day' => $currentDate->day,
                'is_checked' => $checkin !== null,
                'is_today' => $currentDate->isToday(),
                'is_past' => $currentDate->isPast() && !$currentDate->isToday(),
                'is_future' => $currentDate->isFuture(),
                'consecutive_days' => $checkin ? $checkin->consecutive_days : null,
                'reward_points' => $checkin ? $checkin->reward_points : null,
                'is_makeup' => $checkin ? $checkin->is_makeup : false,
            ];

            $currentDate->addDay();
        }

        return [
            'year' => $year,
            'month' => $month,
            'calendar' => $calendar,
            'stat' => [
                'total_days' => $stat->total_days,
                'current_consecutive_days' => $stat->current_consecutive_days,
                'max_consecutive_days' => $stat->max_consecutive_days,
                'last_checkin_date' => $stat->last_checkin_date?->format('Y-m-d'),
            ],
        ];
    }

    /**
     * 补签
     */
    public function makeupCheckin(User $user, Carbon $date): UserCheckin
    {
        // 补签日期不能是今天或未来
        if ($date->isToday() || $date->isFuture()) {
            throw new \Exception('只能补签过去的日期');
        }

        // 检查该日期是否已经签到
        $existingCheckin = UserCheckin::where('user_id', $user->id)
            ->whereDate('checkin_date', $date)
            ->first();

        if ($existingCheckin) {
            throw new \Exception('该日期已签到，无需补签');
        }

        // 补签需要消耗积分（消耗50积分）
        $makeupCost = 50;
        $memberPoint = $this->pointService->getPoints($user);

        if ($memberPoint->available_points < $makeupCost) {
            throw new \Exception("补签需要消耗{$makeupCost}积分，您的积分不足");
        }

        return DB::transaction(function () use ($user, $date, $makeupCost) {
            // 扣除补签消耗的积分
            $this->pointService->spendPoints(
                $user,
                $makeupCost,
                'makeup_checkin',
                null,
                '补签消耗'
            );

            // 计算连续签到天数（补签不更新连续天数，使用当前连续天数）
            $stat = $this->getOrCreateStat($user);
            $consecutiveDays = $stat->current_consecutive_days > 0 ? $stat->current_consecutive_days : 1;
            
            // 计算奖励积分
            $rewardPoints = $this->calculateRewardPoints($consecutiveDays);

            // 创建补签记录
            $checkin = UserCheckin::create([
                'user_id' => $user->id,
                'checkin_date' => $date,
                'consecutive_days' => $consecutiveDays,
                'reward_points' => $rewardPoints,
                'is_makeup' => true,
            ]);

            // 发放积分奖励
            if ($rewardPoints > 0) {
                $this->pointService->earnPoints(
                    $user,
                    $rewardPoints,
                    'checkin',
                    $checkin->id,
                    "补签奖励（连续{$consecutiveDays}天）"
                );
            }

            // 更新签到统计（补签只增加总天数，不更新连续天数）
            $stat->total_days = $stat->total_days + 1;
            $stat->makeup_count = ($stat->makeup_count ?? 0) + 1;
            $stat->save();

            Log::info('用户补签成功', [
                'user_id' => $user->id,
                'checkin_date' => $date->toDateString(),
                'cost_points' => $makeupCost,
                'reward_points' => $rewardPoints,
            ]);

            return $checkin;
        });
    }

    /**
     * 获取签到任务模板ID（用于完成任务）
     */
    private function getCheckinTaskTemplateId(): int
    {
        $template = \App\Models\TaskTemplate::where('category', 'sign')
            ->where('type', 'daily')
            ->where('is_active', true)
            ->first();

        return $template ? $template->id : 0;
    }

    /**
     * 获取用户签到统计
     */
    public function getCheckinStat(User $user): array
    {
        $startTime = microtime(true);
        
        \Illuminate\Support\Facades\Log::info('【签到统计-开始】', [
            'user_id' => $user->id,
            'user_nickname' => $user->nickname,
        ]);
        
        $statStart = microtime(true);
        $stat = $this->getOrCreateStat($user);
        $statTime = microtime(true) - $statStart;
        
        \Illuminate\Support\Facades\Log::info('【签到统计-获取统计】', [
            'user_id' => $user->id,
            'stat_id' => $stat->id,
            'total_days' => $stat->total_days,
            'was_recently_created' => $stat->wasRecentlyCreated ? 'yes' : 'no',
            'get_time' => round($statTime, 3),
        ]);

        // 检查今天是否已签到
        $todayCheckinStart = microtime(true);
        $todayCheckin = UserCheckin::where('user_id', $user->id)
            ->whereDate('checkin_date', Carbon::today())
            ->first();
        $todayCheckinTime = microtime(true) - $todayCheckinStart;
        
        \Illuminate\Support\Facades\Log::info('【签到统计-查询今日签到】', [
            'user_id' => $user->id,
            'has_today_checkin' => $todayCheckin ? 'yes' : 'no',
            'query_time' => round($todayCheckinTime, 3),
        ]);
        
        $totalTime = microtime(true) - $startTime;
        \Illuminate\Support\Facades\Log::info('【签到统计-完成】', [
            'user_id' => $user->id,
            'total_time' => round($totalTime, 3),
        ]);

        return [
            'total_days' => $stat->total_days,
            'current_consecutive_days' => $stat->current_consecutive_days,
            'max_consecutive_days' => $stat->max_consecutive_days,
            'last_checkin_date' => $stat->last_checkin_date?->format('Y-m-d'),
            'is_checked_today' => $todayCheckin !== null,
            'today_reward_points' => $todayCheckin ? $todayCheckin->reward_points : null,
            'makeup_count' => $stat->makeup_count ?? 0,
        ];
    }
}

