<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin\V1;

use App\Http\Controllers\Controller;
use App\Models\LotteryActivity;
use App\Models\LotteryPrize;
use App\Models\LotteryRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LotteryController extends Controller
{
    /**
     * 获取抽奖活动列表
     */
    public function index(Request $request): JsonResponse
    {
        $query = LotteryActivity::with('prizes');

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('name', 'like', "%{$search}%");
        }

        $activities = $query->orderBy('sort_order')
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('page_size', 15));

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'activities' => $activities->items(),
                'pagination' => [
                    'total' => $activities->total(),
                    'current_page' => $activities->currentPage(),
                    'last_page' => $activities->lastPage(),
                    'per_page' => $activities->perPage(),
                ],
            ],
        ]);
    }

    /**
     * 获取抽奖活动详情
     */
    public function show(int $id): JsonResponse
    {
        $activity = LotteryActivity::with(['prizes.coupon', 'prizes.dish'])->findOrFail($id);
        
        // 为每个奖品计算剩余库存
        $activity->prizes->each(function ($prize) {
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
            $prize->is_available = $prize->is_active && 
                ($prize->stock == 0 || $prize->remaining_stock > 0) &&
                ($prize->daily_stock == 0 || $prize->remaining_daily_stock > 0);
        });

        // 统计信息
        $totalRecords = LotteryRecord::where('lottery_activity_id', $id)->count();
        $winnerRecords = LotteryRecord::where('lottery_activity_id', $id)->where('is_winner', true)->count();

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'activity' => $activity,
                'statistics' => [
                    'total_draws' => $totalRecords,
                    'total_winners' => $winnerRecords,
                    'win_rate' => $totalRecords > 0 ? round($winnerRecords / $totalRecords * 100, 2) : 0,
                ],
            ],
        ]);
    }

    /**
     * 创建抽奖活动
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:1000',
            'image_url' => 'nullable|url|max:255',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'daily_limit' => 'nullable|integer|min:0',
            'total_limit' => 'nullable|integer|min:0',
            'points_cost' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer',
        ]);

        $activity = LotteryActivity::create($request->all());

        return response()->json([
            'code' => 201,
            'message' => '抽奖活动创建成功',
            'data' => [
                'activity' => $activity,
            ],
        ], 201);
    }

    /**
     * 更新抽奖活动
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $activity = LotteryActivity::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|string|max:100',
            'description' => 'nullable|string|max:1000',
            'image_url' => 'nullable|url|max:255',
            'start_time' => 'sometimes|date',
            'end_time' => 'sometimes|date|after:start_time',
            'daily_limit' => 'nullable|integer|min:0',
            'total_limit' => 'nullable|integer|min:0',
            'points_cost' => 'nullable|integer|min:0',
            'is_active' => 'sometimes|boolean',
            'sort_order' => 'nullable|integer',
        ]);

        $activity->update($request->all());

        return response()->json([
            'code' => 200,
            'message' => '抽奖活动更新成功',
            'data' => [
                'activity' => $activity,
            ],
        ]);
    }

    /**
     * 删除抽奖活动
     */
    public function destroy(int $id): JsonResponse
    {
        $activity = LotteryActivity::findOrFail($id);

        // 检查是否有抽奖记录
        $recordCount = LotteryRecord::where('lottery_activity_id', $id)->count();
        if ($recordCount > 0) {
            return response()->json([
                'code' => 400,
                'message' => '该活动已有抽奖记录，无法删除',
            ], 400);
        }

        $activity->delete();

        return response()->json([
            'code' => 200,
            'message' => '抽奖活动删除成功',
        ]);
    }

    /**
     * 获取奖品列表
     */
    public function prizes(Request $request, int $activityId): JsonResponse
    {
        $query = LotteryPrize::where('lottery_activity_id', $activityId)
            ->with(['coupon', 'dish']);

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $prizes = $query->orderBy('sort_order')
            ->orderBy('created_at', 'desc')
            ->get();
        
        // 为每个奖品计算剩余库存
        $prizes->each(function ($prize) {
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
            $prize->is_available = $prize->is_active && 
                ($prize->stock == 0 || $prize->remaining_stock > 0) &&
                ($prize->daily_stock == 0 || $prize->remaining_daily_stock > 0);
        });

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'prizes' => $prizes,
            ],
        ]);
    }

    /**
     * 创建奖品
     */
    public function storePrize(Request $request, int $activityId): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'image_url' => 'nullable|url|max:255',
            'prize_type' => 'required|in:coupon,points,dish',
            'prize_id' => 'required|integer',
            'prize_value' => 'nullable|integer|min:0',
            'probability' => 'required|integer|min:1|max:10000',
            'stock' => 'nullable|integer|min:0',
            'daily_stock' => 'nullable|integer|min:0',
            'sort_order' => 'nullable|integer',
            'is_active' => 'boolean',
        ]);

        // 验证奖品ID是否存在
        switch ($request->input('prize_type')) {
            case 'coupon':
                \App\Models\Coupon::findOrFail($request->input('prize_id'));
                break;
            case 'dish':
                \App\Models\Dish::findOrFail($request->input('prize_id'));
                break;
        }

        $prize = LotteryPrize::create([
            'lottery_activity_id' => $activityId,
            ...$request->all(),
        ]);

        return response()->json([
            'code' => 201,
            'message' => '奖品创建成功',
            'data' => [
                'prize' => $prize,
            ],
        ], 201);
    }

    /**
     * 更新奖品
     */
    public function updatePrize(Request $request, int $activityId, int $prizeId): JsonResponse
    {
        $prize = LotteryPrize::where('lottery_activity_id', $activityId)
            ->findOrFail($prizeId);

        $request->validate([
            'name' => 'sometimes|string|max:100',
            'description' => 'nullable|string|max:500',
            'image_url' => 'nullable|url|max:255',
            'prize_type' => 'sometimes|in:coupon,points,dish',
            'prize_id' => 'sometimes|integer',
            'prize_value' => 'nullable|integer|min:0',
            'probability' => 'sometimes|integer|min:1|max:10000',
            'stock' => 'nullable|integer|min:0',
            'daily_stock' => 'nullable|integer|min:0',
            'sort_order' => 'nullable|integer',
            'is_active' => 'sometimes|boolean',
        ]);

        $prize->update($request->all());

        return response()->json([
            'code' => 200,
            'message' => '奖品更新成功',
            'data' => [
                'prize' => $prize,
            ],
        ]);
    }

    /**
     * 删除奖品
     */
    public function destroyPrize(int $activityId, int $prizeId): JsonResponse
    {
        $prize = LotteryPrize::where('lottery_activity_id', $activityId)
            ->findOrFail($prizeId);

        // 检查是否有中奖记录
        $recordCount = LotteryRecord::where('lottery_prize_id', $prizeId)
            ->where('is_winner', true)
            ->count();
        
        if ($recordCount > 0) {
            return response()->json([
                'code' => 400,
                'message' => '该奖品已有中奖记录，无法删除',
            ], 400);
        }

        $prize->delete();

        return response()->json([
            'code' => 200,
            'message' => '奖品删除成功',
        ]);
    }

    /**
     * 获取抽奖记录
     */
    public function records(Request $request, int $activityId): JsonResponse
    {
        $query = LotteryRecord::where('lottery_activity_id', $activityId)
            ->with(['user', 'prize']);

        if ($request->has('is_winner')) {
            $query->where('is_winner', $request->boolean('is_winner'));
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        $records = $query->orderBy('created_at', 'desc')
            ->paginate($request->input('page_size', 20));

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'records' => $records->items(),
                'pagination' => [
                    'total' => $records->total(),
                    'current_page' => $records->currentPage(),
                    'last_page' => $records->lastPage(),
                    'per_page' => $records->perPage(),
                ],
            ],
        ]);
    }
}

