<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin\V1;

use App\Http\Controllers\Controller;
use App\Models\UserCheckin;
use App\Models\UserCheckinStat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CheckinStatisticsController extends Controller
{
    /**
     * 获取签到统计数据
     */
    public function statistics(Request $request): JsonResponse
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $query = UserCheckin::query();

        if ($startDate) {
            $query->where('checkin_date', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('checkin_date', '<=', $endDate);
        }

        // 总体统计
        $totalCheckins = (clone $query)->count();
        $uniqueUsers = (clone $query)->distinct('user_id')->count('user_id');
        $totalRewardPoints = (clone $query)->sum('reward_points');
        $makeupCheckins = (clone $query)->where('is_makeup', true)->count();

        // 按日期统计
        $byDate = (clone $query)
            ->selectRaw('checkin_date, COUNT(*) as count, SUM(reward_points) as total_points')
            ->groupBy('checkin_date')
            ->orderBy('checkin_date')
            ->get()
            ->map(fn($item) => [
                'date' => $item->checkin_date,
                'count' => $item->count,
                'total_points' => $item->total_points ?? 0,
            ])
            ->toArray();

        // 签到达人排行榜（累计签到天数最多的用户）
        $topCheckinUsers = UserCheckinStat::orderByDesc('total_days')
            ->limit(10)
            ->with('user:id,nickname,avatar_url')
            ->get()
            ->map(fn($item) => [
                'user_id' => $item->user_id,
                'nickname' => $item->user->nickname ?? '未知用户',
                'avatar_url' => $item->user->avatar_url,
                'total_days' => $item->total_days,
                'max_consecutive_days' => $item->max_consecutive_days,
                'current_consecutive_days' => $item->current_consecutive_days,
            ])
            ->toArray();

        // 连续签到排行榜
        $topConsecutiveUsers = UserCheckinStat::orderByDesc('max_consecutive_days')
            ->limit(10)
            ->with('user:id,nickname,avatar_url')
            ->get()
            ->map(fn($item) => [
                'user_id' => $item->user_id,
                'nickname' => $item->user->nickname ?? '未知用户',
                'avatar_url' => $item->user->avatar_url,
                'max_consecutive_days' => $item->max_consecutive_days,
            ])
            ->toArray();

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'total_checkins' => $totalCheckins,
                'unique_users' => $uniqueUsers,
                'total_reward_points' => $totalRewardPoints ?? 0,
                'makeup_checkins' => $makeupCheckins,
                'by_date' => $byDate,
                'top_checkin_users' => $topCheckinUsers,
                'top_consecutive_users' => $topConsecutiveUsers,
            ],
        ]);
    }

    /**
     * 获取签到列表
     */
    public function index(Request $request): JsonResponse
    {
        $query = UserCheckin::with('user:id,nickname,avatar_url');

        if ($request->has('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        if ($request->has('start_date')) {
            $query->where('checkin_date', '>=', $request->input('start_date'));
        }

        if ($request->has('end_date')) {
            $query->where('checkin_date', '<=', $request->input('end_date'));
        }

        if ($request->has('is_makeup')) {
            $query->where('is_makeup', $request->boolean('is_makeup'));
        }

        $checkins = $query->orderBy('checkin_date', 'desc')->paginate($request->input('page_size', 20));

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'checkins' => $checkins->items(),
                'pagination' => [
                    'total' => $checkins->total(),
                    'current_page' => $checkins->currentPage(),
                    'last_page' => $checkins->lastPage(),
                    'per_page' => $checkins->perPage(),
                ],
            ],
        ]);
    }
}

