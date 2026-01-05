<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Models\UserShare;
use App\Services\PointService;
use App\Services\TaskService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ShareService
{
    // 分享奖励积分配置
    private const REWARD_POINTS = [
        'review' => 20,      // 分享评价：20积分
        'order' => 30,       // 分享订单：30积分
        'achievement' => 50, // 分享成就：50积分
        'task' => 15,        // 分享任务：15积分
    ];

    // 每日分享奖励限制：同一类型同一内容最多获得3次奖励
    private const DAILY_REWARD_LIMIT = 3;

    public function __construct(
        private PointService $pointService,
        private TaskService $taskService
    ) {
    }

    /**
     * 记录分享
     */
    public function recordShare(
        User $user,
        string $shareType,
        int $shareContentId,
        string $sharePlatform = 'moments'
    ): UserShare {
        return DB::transaction(function () use ($user, $shareType, $shareContentId, $sharePlatform) {
            // 检查是否可以获得奖励
            $canGetReward = $this->canGetShareReward($user, $shareType, $shareContentId);
            $rewardPoints = $canGetReward ? (self::REWARD_POINTS[$shareType] ?? 0) : 0;

            // 创建分享记录
            $userShare = UserShare::create([
                'user_id' => $user->id,
                'share_type' => $shareType,
                'share_content_id' => $shareContentId,
                'share_platform' => $sharePlatform,
                'reward_points' => $rewardPoints,
                'reward_issued' => false,
            ]);

            // 如果可以获得奖励，发放奖励
            if ($canGetReward && $rewardPoints > 0) {
                $this->issueShareReward($userShare);
            }

            // 检测任务完成（分享任务）
            try {
                $this->taskService->checkTaskCompletion($user, 'share', 1);
            } catch (\Exception $e) {
                Log::warning('分享任务完成检测失败', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }

            Log::info('用户分享记录已创建', [
                'user_id' => $user->id,
                'share_type' => $shareType,
                'share_content_id' => $shareContentId,
                'share_platform' => $sharePlatform,
                'reward_points' => $rewardPoints,
            ]);

            return $userShare;
        });
    }

    /**
     * 检查是否可以获得分享奖励
     * 每日限制：同一类型同一内容最多获得3次奖励
     */
    public function canGetShareReward(User $user, string $shareType, int $shareContentId): bool
    {
        $today = Carbon::today();

        // 统计今天该用户分享该类型该内容的次数（已获得奖励的）
        $todayShareCount = UserShare::where('user_id', $user->id)
            ->where('share_type', $shareType)
            ->where('share_content_id', $shareContentId)
            ->where('reward_issued', true)
            ->whereDate('created_at', $today)
            ->count();

        return $todayShareCount < self::DAILY_REWARD_LIMIT;
    }

    /**
     * 发放分享奖励
     */
    public function issueShareReward(UserShare $userShare): void
    {
        if ($userShare->reward_issued) {
            return; // 奖励已发放
        }

        if ($userShare->reward_points <= 0) {
            return; // 没有奖励积分
        }

        DB::transaction(function () use ($userShare) {
            $user = $userShare->user;

            // 发放积分奖励
            $this->pointService->earnPoints(
                $user,
                $userShare->reward_points,
                'share',
                $userShare->id,
                "分享奖励（{$userShare->share_type}）"
            );

            // 标记奖励已发放
            $userShare->update(['reward_issued' => true]);

            Log::info('分享奖励已发放', [
                'user_share_id' => $userShare->id,
                'user_id' => $user->id,
                'share_type' => $userShare->share_type,
                'reward_points' => $userShare->reward_points,
            ]);
        });
    }

    /**
     * 获取用户的分享统计
     */
    public function getShareStats(User $user, ?string $shareType = null): array
    {
        $query = UserShare::where('user_id', $user->id);

        if ($shareType) {
            $query->where('share_type', $shareType);
        }

        $totalShares = $query->count();
        $rewardedShares = (clone $query)->where('reward_issued', true)->count();
        $totalRewardPoints = (clone $query)->where('reward_issued', true)->sum('reward_points');

        // 按类型统计
        $byType = UserShare::where('user_id', $user->id)
            ->selectRaw('share_type, COUNT(*) as count, SUM(CASE WHEN reward_issued = 1 THEN reward_points ELSE 0 END) as total_points')
            ->groupBy('share_type')
            ->get()
            ->keyBy('share_type')
            ->map(function ($item) {
                return [
                    'count' => $item->count,
                    'total_points' => $item->total_points ?? 0,
                ];
            })
            ->toArray();

        return [
            'total_shares' => $totalShares,
            'rewarded_shares' => $rewardedShares,
            'total_reward_points' => $totalRewardPoints ?? 0,
            'by_type' => $byType,
        ];
    }

    /**
     * 获取用户的分享列表
     */
    public function getUserShares(User $user, ?string $shareType = null, int $limit = 20): array
    {
        $query = UserShare::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit($limit);

        if ($shareType) {
            $query->where('share_type', $shareType);
        }

        return $query->get()->toArray();
    }
}

