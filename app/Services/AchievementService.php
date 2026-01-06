<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Models\UserAchievement;
use App\Models\AchievementTemplate;
use App\Models\UserCoupon;
use App\Services\PointService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AchievementService
{
    public function __construct(
        private PointService $pointService
    ) {
    }

    /**
     * 检测成就完成
     */
    public function checkAchievementCompletion(User $user, string $category, int $increment = 1, ?array $extraData = null): void
    {
        // 【修复死循环】如果正在处理成就完成，避免递归调用
        static $processing = [];
        $key = $user->id . '_' . $category;
        if (isset($processing[$key]) && $processing[$key] === true) {
            Log::warning('【成就检测-避免递归】', [
                'user_id' => $user->id,
                'category' => $category,
            ]);
            return;
        }
        $processing[$key] = true;
        
        try {
        // 获取该分类的所有启用的成就模板
        $templates = AchievementTemplate::where('category', $category)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        foreach ($templates as $template) {
            // 获取或创建用户成就记录
            $userAchievement = UserAchievement::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'achievement_template_id' => $template->id,
                ],
                [
                    'progress' => $this->getInitialProgress($user, $template),
                    'reward_issued' => false,
                ]
            );

            // 如果已完成，跳过
            if ($userAchievement->completed_at) {
                continue;
            }

            // 更新进度
            $this->updateAchievementProgress($userAchievement, $template, $increment, $extraData);
            }
        } finally {
            // 【修复死循环】清除处理标志
            unset($processing[$key]);
        }
    }

    /**
     * 更新成就进度
     */
    public function updateAchievementProgress(UserAchievement $userAchievement, AchievementTemplate $template, int $increment = 1, ?array $extraData = null): void
    {
        $progress = $userAchievement->progress ?? ['current' => 0, 'target' => 0];
        $targetValue = $template->target_value ?? [];

        // 根据target_value类型决定如何更新进度
        if (isset($targetValue['amount'])) {
            // 累计金额类型：需要重新计算总金额
            $current = $this->calculateCurrentProgress($userAchievement->user, $template);
        } else {
            // 计数类型：累加increment
            $current = ($progress['current'] ?? 0) + $increment;
        }

        $target = $this->getTargetValue($template);

        // 更新进度
        $userAchievement->update([
            'progress' => ['current' => $current, 'target' => $target],
        ]);

        // 如果完成，发放奖励
        if ($current >= $target && !$userAchievement->completed_at) {
            $this->completeAchievement($userAchievement);
        }
    }

    /**
     * 完成成就并发放奖励
     */
    public function completeAchievement(UserAchievement $userAchievement): void
    {
        if ($userAchievement->reward_issued) {
            return; // 奖励已发放
        }

        // 【修复死循环】如果正在完成成就，避免递归调用
        static $completing = [];
        $key = $userAchievement->id;
        if (isset($completing[$key]) && $completing[$key] === true) {
            Log::warning('【成就完成-避免递归】', [
                'user_achievement_id' => $userAchievement->id,
            ]);
            return;
        }
        $completing[$key] = true;

        try {
        DB::transaction(function () use ($userAchievement) {
                // 确保关联已加载，避免N+1查询
                if (!$userAchievement->relationLoaded('achievementTemplate')) {
                    $userAchievement->load('achievementTemplate');
                }
                if (!$userAchievement->relationLoaded('user')) {
                    $userAchievement->load('user');
                }
                
            $template = $userAchievement->achievementTemplate;
            $user = $userAchievement->user;

                if (!$template || !$user) {
                    Log::warning('成就完成失败：缺少必要数据', [
                        'user_achievement_id' => $userAchievement->id,
                    ]);
                    return;
                }

            // 发放积分奖励
            if ($template->reward_points > 0) {
                $this->pointService->earnPoints(
                    $user,
                    $template->reward_points,
                    'achievement',
                    $userAchievement->id,
                    "完成成就：{$template->name}"
                );
            }

            // 发放优惠券奖励
            if ($template->reward_coupon_id) {
                    // 确保优惠券关联已加载
                    if (!$template->relationLoaded('rewardCoupon')) {
                        $template->load('rewardCoupon');
                    }
                    
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

            // 标记完成和奖励已发放
            $userAchievement->update([
                'completed_at' => now(),
                'reward_issued' => true,
            ]);

            Log::info('成就完成，奖励已发放', [
                'user_achievement_id' => $userAchievement->id,
                'user_id' => $user->id,
                'achievement_template_id' => $template->id,
                'achievement_name' => $template->name,
                'reward_points' => $template->reward_points,
            ]);
        });
        } finally {
            // 【修复死循环】清除完成标志
            unset($completing[$key]);
        }
    }

    /**
     * 获取用户的成就列表（确保所有成就模板都有对应的用户成就记录）
     */
    public function getUserAchievements(User $user, ?string $category = null): array
    {
        // 【调试日志】开始处理成就查询
        Log::info('【成就查询开始】', [
            'user_id' => $user->id,
            'user_nickname' => $user->nickname,
            'category' => $category,
            'has_member_point' => $user->memberPoints ? 'yes' : 'no',
        ]);
        
        // 【性能优化】设置执行时间限制，避免超时
        $startTime = microtime(true);
        
        // 获取所有启用的成就模板
        $templatesQueryStart = microtime(true);
        $templatesQuery = AchievementTemplate::where('is_active', true);
        
        if ($category) {
            $templatesQuery->where('category', $category);
        }
        
        $templates = $templatesQuery->orderBy('category')
            ->orderBy('sort_order')
            ->get();
        $templatesQueryTime = microtime(true) - $templatesQueryStart;
        
        Log::info('【成就查询-模板查询】', [
            'user_id' => $user->id,
            'template_count' => $templates->count(),
            'query_time' => round($templatesQueryTime, 3),
        ]);

        if ($templates->isEmpty()) {
            Log::info('【成就查询-无模板】', ['user_id' => $user->id]);
            return [];
        }

        // 预加载用户相关数据，避免N+1查询问题
        $preloadStart = microtime(true);
        Log::info('【成就查询-开始预加载】', ['user_id' => $user->id]);
        $this->preloadUserData($user);
        $preloadTime = microtime(true) - $preloadStart;
        
        Log::info('【成就查询-预加载完成】', [
            'user_id' => $user->id,
            'preload_time' => round($preloadTime, 3),
            'has_member_point_after' => $user->memberPoints ? 'yes' : 'no',
            'has_checkin_stat' => $user->checkinStat ? 'yes' : 'no',
            'cached_order_stats' => isset($user->_cached_order_stats) ? 'yes' : 'no',
            'cached_review_stats' => isset($user->_cached_review_stats) ? 'yes' : 'no',
            'cached_invitation_stats' => isset($user->_cached_invitation_stats) ? 'yes' : 'no',
        ]);
        
        // 如果预加载时间超过5秒，记录警告
        if ($preloadTime > 5) {
            Log::warning('预加载用户数据耗时过长', [
                'user_id' => $user->id,
                'preload_time' => $preloadTime,
            ]);
        }

        // 批量获取所有已存在的用户成就记录，避免循环中的重复查询
        // 预加载所有需要的关联，避免后续N+1查询
        $existingQueryStart = microtime(true);
        $templateIds = $templates->pluck('id')->toArray();
        $existingAchievements = UserAchievement::where('user_id', $user->id)
            ->whereIn('achievement_template_id', $templateIds)
            ->with(['achievementTemplate', 'achievementTemplate.rewardCoupon', 'user'])
            ->get()
            ->keyBy('achievement_template_id');
        $existingQueryTime = microtime(true) - $existingQueryStart;
        
        Log::info('【成就查询-查询已存在成就】', [
            'user_id' => $user->id,
            'existing_count' => $existingAchievements->count(),
            'query_time' => round($existingQueryTime, 3),
        ]);

        $userAchievements = [];
        $achievementsToCreate = [];
        $achievementsToUpdate = [];
        $achievementsToComplete = []; // 延迟处理完成的成就，避免在循环中执行耗时操作
        $progressCache = []; // 缓存进度计算结果，避免重复计算

        $loopStart = microtime(true);
        $templateCount = $templates->count();

        foreach ($templates as $index => $template) {
            $userAchievement = $existingAchievements->get($template->id);

            // 计算进度（只计算一次，缓存结果）
            $calcStart = microtime(true);
            $currentProgress = $this->calculateCurrentProgress($user, $template);
            $target = $this->getTargetValue($template);
            $calcTime = microtime(true) - $calcStart;
            
            // 【调试日志】记录每个成就的计算时间
            if ($calcTime > 0.5 || $index < 5) {
                Log::info('【成就查询-计算进度】', [
                    'user_id' => $user->id,
                    'template_id' => $template->id,
                    'template_category' => $template->category,
                    'template_name' => $template->name,
                    'current_progress' => $currentProgress,
                    'target' => $target,
                    'calc_time' => round($calcTime, 3),
                ]);
            }
            
            // 如果单个成就计算时间超过1秒，记录警告
            if ($calcTime > 1) {
                Log::warning('计算成就进度耗时过长', [
                    'user_id' => $user->id,
                    'template_id' => $template->id,
                    'template_category' => $template->category,
                    'calc_time' => $calcTime,
                ]);
            }
            
            $progressCache[$template->id] = ['current' => $currentProgress, 'target' => $target];
            
            // 每处理10个成就，检查是否超时
            if (($index + 1) % 10 === 0) {
                $elapsed = microtime(true) - $startTime;
                if ($elapsed > 25) {
                    Log::error('成就处理超时风险', [
                        'user_id' => $user->id,
                        'processed' => $index + 1,
                        'total' => $templateCount,
                        'elapsed' => $elapsed,
                    ]);
                    // 提前返回，避免超时
                    break;
                }
            }

            // 如果不存在，准备批量创建
            if (!$userAchievement) {
                $achievementsToCreate[] = [
                    'user_id' => $user->id,
                    'achievement_template_id' => $template->id,
                    'progress' => json_encode($progressCache[$template->id]),
                    'reward_issued' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            } else {
            // 如果未完成，重新计算进度（确保进度是最新的）
            if (!$userAchievement->completed_at) {
                    // 检查进度是否有变化
                    $progress = $userAchievement->progress ?? ['current' => 0, 'target' => 0];
                    if ($progress['current'] != $currentProgress || $progress['target'] != $target) {
                        $userAchievement->progress = $progressCache[$template->id];
                        $achievementsToUpdate[] = $userAchievement;
                    }

                    // 检查是否完成（延迟处理，避免在循环中执行耗时操作）
                if ($currentProgress >= $target) {
                        $achievementsToComplete[] = $userAchievement;
                    }
                }
            }
        }

        // 批量创建新记录
        if (!empty($achievementsToCreate)) {
            UserAchievement::insert($achievementsToCreate);
            // 重新获取新创建的记录
            $newAchievements = UserAchievement::where('user_id', $user->id)
                ->whereIn('achievement_template_id', array_column($achievementsToCreate, 'achievement_template_id'))
                ->with('achievementTemplate')
                ->get()
                ->keyBy('achievement_template_id');
            
            // 合并到现有记录中
            $existingAchievements = $existingAchievements->merge($newAchievements);
        }

        // 批量更新记录
        if (!empty($achievementsToUpdate)) {
            foreach ($achievementsToUpdate as $achievement) {
                $achievement->save();
            }
        }

        // 延迟处理完成的成就（避免在循环中执行耗时操作）
        if (!empty($achievementsToComplete)) {
            foreach ($achievementsToComplete as $achievement) {
                // 确保关联已加载
                if (!$achievement->relationLoaded('achievementTemplate')) {
                    $achievement->load('achievementTemplate');
                }
                if (!$achievement->relationLoaded('user')) {
                    $achievement->load('user');
                }
                if ($achievement->achievementTemplate && $achievement->achievementTemplate->reward_coupon_id) {
                    if (!$achievement->achievementTemplate->relationLoaded('rewardCoupon')) {
                        $achievement->achievementTemplate->load('rewardCoupon');
                    }
                }
                
                $this->completeAchievement($achievement);
                $achievement->refresh();
                }
            }

        // 构建返回数据
        foreach ($templates as $template) {
            $userAchievement = $existingAchievements->get($template->id);
            
            // 如果还是没有（理论上不应该发生），使用缓存的进度数据创建临时对象
            if (!$userAchievement) {
                $cachedProgress = $progressCache[$template->id] ?? ['current' => 0, 'target' => 0];
                $userAchievement = (object) [
                    'id' => null,
                    'user_id' => $user->id,
                    'achievement_template_id' => $template->id,
                    'progress' => $cachedProgress,
                    'completed_at' => null,
                    'reward_issued' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // 使用缓存的进度数据（如果存在），否则使用数据库中的数据
            $progress = $progressCache[$template->id] ?? (
                is_array($userAchievement->progress) 
                    ? $userAchievement->progress 
                    : (json_decode($userAchievement->progress ?? '{"current":0,"target":0}', true) ?? ['current' => 0, 'target' => 0])
            );

            $userAchievements[] = [
                'id' => $userAchievement->id,
                'user_id' => $userAchievement->user_id,
                'achievement_template_id' => $userAchievement->achievement_template_id,
                'progress' => $progress,
                'completed_at' => $userAchievement->completed_at?->toDateTimeString(),
                'reward_issued' => $userAchievement->reward_issued ?? false,
                'created_at' => $userAchievement->created_at instanceof \DateTime 
                    ? $userAchievement->created_at->toDateTimeString() 
                    : (is_string($userAchievement->created_at) ? $userAchievement->created_at : now()->toDateTimeString()),
                'updated_at' => $userAchievement->updated_at instanceof \DateTime 
                    ? $userAchievement->updated_at->toDateTimeString() 
                    : (is_string($userAchievement->updated_at) ? $userAchievement->updated_at : now()->toDateTimeString()),
                'achievement_template' => [
                    'id' => $template->id,
                    'name' => $template->name,
                    'description' => $template->description,
                    'icon' => $template->icon,
                    'category' => $template->category,
                    'target_value' => $template->target_value,
                    'reward_points' => $template->reward_points,
                    'reward_coupon_id' => $template->reward_coupon_id,
                    'is_active' => $template->is_active,
                    'sort_order' => $template->sort_order,
                ],
            ];
        }

        $totalTime = microtime(true) - $startTime;
        Log::info('【成就查询-完成】', [
            'user_id' => $user->id,
            'total_time' => round($totalTime, 3),
            'achievements_count' => count($userAchievements),
            'created_count' => count($achievementsToCreate),
            'updated_count' => count($achievementsToUpdate),
            'completed_count' => count($achievementsToComplete),
        ]);

        return $userAchievements;
    }

    /**
     * 预加载用户相关数据，避免N+1查询问题
     */
    private function preloadUserData(User $user): void
    {
        // 【修复死循环】如果已经预加载过，直接返回，避免重复执行
        if (isset($user->_preload_data_loaded) && $user->_preload_data_loaded === true) {
            return;
        }
        
        $stepStart = microtime(true);
        
        // 预加载签到统计
        $checkinStart = microtime(true);
        if (!$user->relationLoaded('checkinStat')) {
            $user->load('checkinStat');
        }
        $checkinTime = microtime(true) - $checkinStart;
        
        Log::info('【预加载-签到统计】', [
            'user_id' => $user->id,
            'has_checkin_stat' => $user->checkinStat ? 'yes' : 'no',
            'load_time' => round($checkinTime, 3),
        ]);

        // 预加载积分数据
        $pointsStart = microtime(true);
        if (!$user->relationLoaded('memberPoints')) {
            $user->load('memberPoints');
        }
        $pointsLoadTime = microtime(true) - $pointsStart;
        
        // 【修复】如果用户没有MemberPoint，立即创建，避免在循环中重复创建
        // 账号密码登录的用户可能没有MemberPoint（因为不是新用户，不会触发UserObserver）
        $createStart = microtime(true);
        if (!$user->memberPoints) {
            Log::info('【预加载-创建MemberPoint】', [
                'user_id' => $user->id,
                'action' => 'creating',
            ]);
            
            // 使用缓存获取默认段位，避免重复查询
            $defaultLevelCode = Cache::remember(
                'point_level_default',
                3600,
                function () {
                    $defaultLevel = \App\Models\PointLevel::getActiveLevels()
                        ->sortBy('min_points')
                        ->first();
                    return $defaultLevel ? $defaultLevel->code : 'bronze';
                }
            );
            
            $memberPoint = \App\Models\MemberPoint::create([
                'user_id' => $user->id,
                'total_points' => 0,
                'available_points' => 0,
                'frozen_points' => 0,
                'level' => $defaultLevelCode,
            ]);
            
            // 重新加载关系
            $user->load('memberPoints');
            
            Log::info('【预加载-MemberPoint创建完成】', [
                'user_id' => $user->id,
                'member_point_id' => $memberPoint->id,
                'create_time' => round(microtime(true) - $createStart, 3),
            ]);
        } else {
            Log::info('【预加载-MemberPoint已存在】', [
                'user_id' => $user->id,
                'member_point_id' => $user->memberPoints->id,
                'load_time' => round($pointsLoadTime, 3),
            ]);
        }

        // 缓存订单统计（避免重复查询）- 使用聚合查询提高性能
        // 【性能优化】使用数据库级别的缓存键，避免每次请求都查询
        $orderStatsStart = microtime(true);
        if (!isset($user->_cached_order_stats)) {
            $cacheKey = "user_order_stats:{$user->id}";
            $user->_cached_order_stats = Cache::remember($cacheKey, 300, function () use ($user) {
                $queryStart = microtime(true);
                $orderStats = $user->orders()
                    ->whereIn('status', ['paid', 'pending_review', 'completed'])
                    ->selectRaw('COUNT(*) as count, COALESCE(SUM(total_amount), 0) as total_amount')
                    ->first();
                $queryTime = microtime(true) - $queryStart;
                
                Log::info('【预加载-订单统计查询】', [
                    'user_id' => $user->id,
                    'query_time' => round($queryTime, 3),
                    'count' => $orderStats->count ?? 0,
                ]);
                
                return [
                    'count' => (int) ($orderStats->count ?? 0),
                    'total_amount' => (int) ($orderStats->total_amount ?? 0),
                ];
            });
        }
        $orderStatsTime = microtime(true) - $orderStatsStart;
        Log::info('【预加载-订单统计】', [
            'user_id' => $user->id,
            'from_cache' => Cache::has("user_order_stats:{$user->id}") ? 'yes' : 'no',
            'total_time' => round($orderStatsTime, 3),
        ]);

        // 缓存评价统计
        $reviewStatsStart = microtime(true);
        if (!isset($user->_cached_review_stats)) {
            $cacheKey = "user_review_stats:{$user->id}";
            $user->_cached_review_stats = Cache::remember($cacheKey, 300, function () use ($user) {
                $queryStart = microtime(true);
                $count = $user->reviews()
                    ->where('status', 'approved')
                    ->count();
                $queryTime = microtime(true) - $queryStart;
                
                Log::info('【预加载-评价统计查询】', [
                    'user_id' => $user->id,
                    'query_time' => round($queryTime, 3),
                    'count' => $count,
                ]);
                
                return $count;
            });
        }
        $reviewStatsTime = microtime(true) - $reviewStatsStart;
        Log::info('【预加载-评价统计】', [
            'user_id' => $user->id,
            'from_cache' => Cache::has("user_review_stats:{$user->id}") ? 'yes' : 'no',
            'total_time' => round($reviewStatsTime, 3),
        ]);

        // 缓存邀请统计
        $invitationStatsStart = microtime(true);
        if (!isset($user->_cached_invitation_stats)) {
            $cacheKey = "user_invitation_stats:{$user->id}";
            $user->_cached_invitation_stats = Cache::remember($cacheKey, 300, function () use ($user) {
                $queryStart = microtime(true);
                $count = $user->invitations()
                    ->where('status', '!=', 'pending')
                    ->count();
                $queryTime = microtime(true) - $queryStart;
                
                Log::info('【预加载-邀请统计查询】', [
                    'user_id' => $user->id,
                    'query_time' => round($queryTime, 3),
                    'count' => $count,
                ]);
                
                return $count;
            });
        }
        $invitationStatsTime = microtime(true) - $invitationStatsStart;
        Log::info('【预加载-邀请统计】', [
            'user_id' => $user->id,
            'from_cache' => Cache::has("user_invitation_stats:{$user->id}") ? 'yes' : 'no',
            'total_time' => round($invitationStatsTime, 3),
        ]);
        
        $totalPreloadTime = microtime(true) - $stepStart;
        
        // 【修复死循环】标记已预加载，避免重复执行
        $user->_preload_data_loaded = true;
        
        Log::info('【预加载-全部完成】', [
            'user_id' => $user->id,
            'total_time' => round($totalPreloadTime, 3),
        ]);
    }

    /**
     * 获取初始进度（用于新创建的成就）
     */
    private function getInitialProgress(User $user, AchievementTemplate $template): array
    {
        // 确保已预加载数据
        $this->preloadUserData($user);
        
        $targetValue = $template->target_value ?? [];
        $current = $this->calculateCurrentProgress($user, $template);
        $target = $this->getTargetValue($template);

        return [
            'current' => $current,
            'target' => $target,
        ];
    }

    /**
     * 计算当前进度（用于成就）
     */
    private function calculateCurrentProgress(User $user, AchievementTemplate $template): int
    {
        $calcStart = microtime(true);
        $targetValue = $template->target_value ?? [];

        if ($template->category === 'consume') {
            if (isset($targetValue['count'])) {
                // 累计订单数量 - 使用缓存数据
                return $user->_cached_order_stats['count'] ?? 0;
            } elseif (isset($targetValue['amount'])) {
                // 累计消费金额 - 使用缓存数据
                return (int) ($user->_cached_order_stats['total_amount'] ?? 0);
            }
        } elseif ($template->category === 'review') {
            if (isset($targetValue['count'])) {
                return $user->_cached_review_stats ?? 0;
            }
        } elseif ($template->category === 'invite') {
            if (isset($targetValue['count'])) {
                return $user->_cached_invitation_stats ?? 0;
            }
        } elseif ($template->category === 'checkin') {
            // 确保已加载签到统计（已在preloadUserData中预加载）
            // 如果不存在，返回0（避免触发firstOrCreate导致性能问题）
            if (!$user->relationLoaded('checkinStat')) {
                $user->load('checkinStat');
            }
            
            $checkinStat = $user->checkinStat;
            if (!$checkinStat) {
                return 0; // 如果签到统计不存在，返回0
            }
            
            if (isset($targetValue['days'])) {
                return $checkinStat->total_days ?? 0;
            } elseif (isset($targetValue['consecutive_days'])) {
                // 连续签到成就应该使用当前连续签到天数，而不是历史最大
                // 因为用户可能中断后重新开始，但连续签到成就是基于当前连续天数的
                return $checkinStat->current_consecutive_days ?? 0;
            }
        } elseif ($template->category === 'points') {
            if (isset($targetValue['total_points'])) {
                // 确保已加载积分数据（已在preloadUserData中预加载和创建）
                // 直接使用预加载的memberPoints，避免调用getPoints()触发段位更新逻辑
                // getPoints()会调用updateLevel()和getActiveLevels()，导致大量数据库查询
                $memberPoint = $user->memberPoints;
                
                // 【修复】如果memberPoint不存在，说明preloadUserData中创建失败，返回0避免重复创建
                // 这种情况不应该发生，因为preloadUserData已经处理了
                if (!$memberPoint) {
                    Log::warning('【计算进度-积分-MemberPoint不存在】', [
                        'user_id' => $user->id,
                        'template_id' => $template->id,
                    ]);
                    return 0;
                }
                
                $result = $memberPoint->total_points ?? 0;
                $calcTime = microtime(true) - $calcStart;
                if ($calcTime > 0.5) {
                    Log::warning('【计算进度-积分类别耗时】', [
                        'user_id' => $user->id,
                        'template_id' => $template->id,
                        'calc_time' => round($calcTime, 3),
                    ]);
            }
                return $result;
            }
        }

        $calcTime = microtime(true) - $calcStart;
        if ($calcTime > 0.5) {
            Log::warning('【计算进度-其他类别耗时】', [
                'user_id' => $user->id,
                'template_id' => $template->id,
                'template_category' => $template->category,
                'calc_time' => round($calcTime, 3),
            ]);
        }

        return 0;
    }

    /**
     * 获取目标值
     */
    private function getTargetValue(AchievementTemplate $template): int
    {
        $targetValue = $template->target_value ?? [];

        if (isset($targetValue['count'])) {
            return (int) $targetValue['count'];
        } elseif (isset($targetValue['amount'])) {
            return (int) $targetValue['amount'];
        } elseif (isset($targetValue['days'])) {
            return (int) $targetValue['days'];
        } elseif (isset($targetValue['consecutive_days'])) {
            return (int) $targetValue['consecutive_days'];
        } elseif (isset($targetValue['total_points'])) {
            return (int) $targetValue['total_points'];
        }

        return 1;
    }
}

