<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\LotteryActivity;
use App\Services\LotteryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\PointService;

class LotteryController extends Controller
{
    public function __construct(
        private LotteryService $lotteryService,
        private PointService $pointService
    ) {
    }

    /**
     * 获取抽奖活动列表
     */
    public function activities(): JsonResponse
    {
        $activities = LotteryActivity::where('is_active', true)
            ->where('start_time', '<=', now())
            ->where('end_time', '>=', now())
            ->with(['prizes' => function ($query) {
                $query->where('is_active', true)->orderBy('sort_order');
            }])
            ->orderBy('sort_order')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'activities' => $activities,
            ],
        ]);
    }

    /**
     * 获取抽奖活动详情
     */
    public function showActivity(int $id): JsonResponse
    {
        $activity = LotteryActivity::with(['prizes' => function ($query) {
            $query->where('is_active', true)->orderBy('sort_order');
        }, 'prizes.coupon', 'prizes.dish'])->findOrFail($id);

        $user = Auth::user();
        $todayCount = $user ? $this->lotteryService->getTodayDrawCount($user, $id) : 0;
        $totalCount = $user ? $this->lotteryService->getTotalDrawCount($user, $id) : 0;
        
        // 获取用户积分信息
        $userPoints = null;
        if ($user) {
            $memberPoint = $this->pointService->getPoints($user);
            $userPoints = [
                'available_points' => $memberPoint->available_points,
                'total_points' => $memberPoint->total_points,
            ];
        }
        
        // 计算每个奖品的剩余库存和实时概率
        $availablePrizes = $activity->prizes->filter(function ($prize) {
            // 检查是否激活
            if (!$prize->is_active) {
                return false;
            }

            // 检查总库存
            if ($prize->stock > 0) {
                $usedCount = \App\Models\LotteryRecord::where('lottery_prize_id', $prize->id)
                    ->where('is_winner', true)
                    ->count();
                
                if ($usedCount >= $prize->stock) {
                    return false;
                }
            }

            // 检查每日库存
            if ($prize->daily_stock > 0) {
                $todayUsedCount = \App\Models\LotteryRecord::where('lottery_prize_id', $prize->id)
                    ->where('is_winner', true)
                    ->whereDate('created_at', today())
                    ->count();
                
                if ($todayUsedCount >= $prize->daily_stock) {
                    return false;
                }
            }

            return true;
        });
        
        // 计算可用奖品的总概率
        $availableTotalProbability = $availablePrizes->sum('probability');
        
        // 为每个奖品计算剩余库存和实时概率
        $activity->prizes->each(function ($prize) use ($availablePrizes, $availableTotalProbability) {
            // 计算总库存剩余
            $usedCount = \App\Models\LotteryRecord::where('lottery_prize_id', $prize->id)
                ->where('is_winner', true)
                ->count();
            $prize->remaining_stock = $prize->stock > 0 ? max(0, $prize->stock - $usedCount) : null;
            $prize->used_stock = $usedCount;
            
            // 计算每日库存剩余
            $todayUsedCount = \App\Models\LotteryRecord::where('lottery_prize_id', $prize->id)
                ->where('is_winner', true)
                ->whereDate('created_at', today())
                ->count();
            $prize->remaining_daily_stock = $prize->daily_stock > 0 ? max(0, $prize->daily_stock - $todayUsedCount) : null;
            $prize->used_daily_stock = $todayUsedCount;
            
            // 判断是否可用（有库存且激活）
            $isAvailable = $prize->is_active && 
                ($prize->stock == 0 || $prize->remaining_stock > 0) &&
                ($prize->daily_stock == 0 || $prize->remaining_daily_stock > 0);
            $prize->is_available = $isAvailable;
            
            // 计算实时概率（基于可用奖品的总概率）
            if ($isAvailable && $availableTotalProbability > 0) {
                $prize->real_time_probability = ($prize->probability / $availableTotalProbability) * 10000;
            } else {
                $prize->real_time_probability = 0;
            }
        });
        
        // 检查是否可以抽奖
        $canDraw = $activity->isActive() && 
            ($activity->daily_limit == 0 || $todayCount < $activity->daily_limit) &&
            ($activity->total_limit == 0 || $totalCount < $activity->total_limit);
        
        // 如果消耗积分，检查积分是否足够
        if ($canDraw && $activity->points_cost > 0 && $user) {
            $canDraw = $userPoints && $userPoints['available_points'] >= $activity->points_cost;
        }

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'activity' => $activity,
                'user_stats' => [
                    'today_draw_count' => $todayCount,
                    'total_draw_count' => $totalCount,
                    'can_draw' => $canDraw,
                ],
                'user_points' => $userPoints,
            ],
        ]);
    }

    /**
     * 执行抽奖
     */
    public function draw(Request $request, int $activityId): JsonResponse
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json([
                'code' => 401,
                'message' => '请先登录',
            ], 401);
        }

        try {
            $result = $this->lotteryService->draw($user, $activityId);

            return response()->json([
                'code' => 200,
                'message' => $result['is_winner'] ? '恭喜中奖！' : '很遗憾，未中奖',
                'data' => [
                    'is_winner' => $result['is_winner'],
                    'prize' => $result['prize'],
                    'record' => $result['record'],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 400,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * 获取我的抽奖记录
     */
    public function myRecords(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json([
                'code' => 401,
                'message' => '请先登录',
            ], 401);
        }

        $activityId = $request->input('activity_id');
        $query = \App\Models\LotteryRecord::where('user_id', $user->id)
            ->with(['activity', 'prize']);

        if ($activityId) {
            $query->where('lottery_activity_id', $activityId);
        }

        $records = $query->orderBy('created_at', 'desc')
            ->paginate($request->input('page_size', 20));

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'records' => $records->items(),
                'pagination' => [
                    'current_page' => $records->currentPage(),
                    'total_pages' => $records->lastPage(),
                    'total_count' => $records->total(),
                    'page_size' => $records->perPage(),
                ],
            ],
        ]);
    }
}

