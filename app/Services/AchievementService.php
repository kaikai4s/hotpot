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

        DB::transaction(function () use ($userAchievement) {
            $template = $userAchievement->achievementTemplate;
            $user = $userAchievement->user;

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
    }

    /**
     * 获取用户的成就列表（确保所有成就模板都有对应的用户成就记录）
     */
    public function getUserAchievements(User $user, ?string $category = null): array
    {
        // 获取所有启用的成就模板
        $templatesQuery = AchievementTemplate::where('is_active', true);
        
        if ($category) {
            $templatesQuery->where('category', $category);
        }
        
        $templates = $templatesQuery->orderBy('category')
            ->orderBy('sort_order')
            ->get();

        $userAchievements = [];

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

            // 如果未完成，重新计算进度（确保进度是最新的）
            if (!$userAchievement->completed_at) {
                $currentProgress = $this->calculateCurrentProgress($user, $template);
                $target = $this->getTargetValue($template);
                
                $userAchievement->update([
                    'progress' => ['current' => $currentProgress, 'target' => $target],
                ]);

                // 检查是否完成
                if ($currentProgress >= $target) {
                    $this->completeAchievement($userAchievement);
                    $userAchievement->refresh();
                }
            }

            // 加载关联
            $userAchievement->load('achievementTemplate');

            $userAchievements[] = [
                'id' => $userAchievement->id,
                'user_id' => $userAchievement->user_id,
                'achievement_template_id' => $userAchievement->achievement_template_id,
                'progress' => $userAchievement->progress ?? ['current' => 0, 'target' => 0],
                'completed_at' => $userAchievement->completed_at?->toDateTimeString(),
                'reward_issued' => $userAchievement->reward_issued,
                'created_at' => $userAchievement->created_at->toDateTimeString(),
                'updated_at' => $userAchievement->updated_at->toDateTimeString(),
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

        return $userAchievements;
    }

    /**
     * 获取初始进度（用于新创建的成就）
     */
    private function getInitialProgress(User $user, AchievementTemplate $template): array
    {
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
        $targetValue = $template->target_value ?? [];

        if ($template->category === 'consume') {
            if (isset($targetValue['count'])) {
                // 累计订单数量
                return $user->orders()->whereIn('status', ['paid', 'pending_review', 'completed'])->count();
            } elseif (isset($targetValue['amount'])) {
                // 累计消费金额
                return (int) $user->orders()->whereIn('status', ['paid', 'pending_review', 'completed'])->sum('total_amount');
            }
        } elseif ($template->category === 'review') {
            if (isset($targetValue['count'])) {
                return $user->reviews()->where('status', 'approved')->count();
            }
        } elseif ($template->category === 'invite') {
            if (isset($targetValue['count'])) {
                return $user->invitations()->where('status', '!=', 'pending')->count();
            }
        } elseif ($template->category === 'checkin') {
            if (isset($targetValue['days'])) {
                return $user->checkinStat->total_days ?? 0;
            } elseif (isset($targetValue['consecutive_days'])) {
                // 连续签到成就应该使用当前连续签到天数，而不是历史最大
                // 因为用户可能中断后重新开始，但连续签到成就是基于当前连续天数的
                return $user->checkinStat->current_consecutive_days ?? 0;
            }
        } elseif ($template->category === 'points') {
            if (isset($targetValue['total_points'])) {
                $memberPoint = $this->pointService->getPoints($user);
                return $memberPoint->total_points;
            }
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

