<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin\V1;

use App\Http\Controllers\Controller;
use App\Models\UserShare;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShareStatisticsController extends Controller
{
    /**
     * 获取分享统计数据
     */
    public function statistics(Request $request): JsonResponse
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $query = UserShare::query();

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('created_at', '<=', $endDate . ' 23:59:59');
        }

        // 总体统计
        $totalShares = (clone $query)->count();
        $uniqueUsers = (clone $query)->distinct('user_id')->count('user_id');
        $rewardedShares = (clone $query)->where('reward_issued', true)->count();
        $totalRewardPoints = (clone $query)->where('reward_issued', true)->sum('reward_points');

        // 按类型统计
        $byType = (clone $query)
            ->selectRaw('share_type, COUNT(*) as count, SUM(CASE WHEN reward_issued = 1 THEN reward_points ELSE 0 END) as total_points')
            ->groupBy('share_type')
            ->get()
            ->map(fn($item) => [
                'type' => $item->share_type,
                'count' => $item->count,
                'total_points' => $item->total_points ?? 0,
            ])
            ->toArray();

        // 按平台统计
        $byPlatform = (clone $query)
            ->selectRaw('share_platform, COUNT(*) as count')
            ->groupBy('share_platform')
            ->get()
            ->map(fn($item) => [
                'platform' => $item->share_platform,
                'count' => $item->count,
            ])
            ->toArray();

        // 按日期统计
        $byDate = (clone $query)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count, SUM(CASE WHEN reward_issued = 1 THEN reward_points ELSE 0 END) as total_points')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn($item) => [
                'date' => $item->date,
                'count' => $item->count,
                'total_points' => $item->total_points ?? 0,
            ])
            ->toArray();

        // 分享达人排行榜
        $topSharers = UserShare::select('user_id', DB::raw('COUNT(*) as share_count'))
            ->groupBy('user_id')
            ->orderByDesc('share_count')
            ->limit(10)
            ->with('user:id,nickname,avatar_url')
            ->get()
            ->map(fn($item) => [
                'user_id' => $item->user_id,
                'nickname' => $item->user->nickname ?? '未知用户',
                'avatar_url' => $item->user->avatar_url,
                'share_count' => $item->share_count,
            ])
            ->toArray();

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'total_shares' => $totalShares,
                'unique_users' => $uniqueUsers,
                'rewarded_shares' => $rewardedShares,
                'total_reward_points' => $totalRewardPoints ?? 0,
                'by_type' => $byType,
                'by_platform' => $byPlatform,
                'by_date' => $byDate,
                'top_sharers' => $topSharers,
            ],
        ]);
    }

    /**
     * 获取分享列表
     */
    public function index(Request $request): JsonResponse
    {
        $query = UserShare::with('user:id,nickname,avatar_url');

        if ($request->has('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        if ($request->has('share_type')) {
            $query->where('share_type', $request->input('share_type'));
        }

        if ($request->has('share_platform')) {
            $query->where('share_platform', $request->input('share_platform'));
        }

        if ($request->has('start_date')) {
            $query->where('created_at', '>=', $request->input('start_date'));
        }

        if ($request->has('end_date')) {
            $query->where('created_at', '<=', $request->input('end_date') . ' 23:59:59');
        }

        $shares = $query->orderBy('created_at', 'desc')->paginate($request->input('page_size', 20));

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'shares' => $shares->items(),
                'pagination' => [
                    'total' => $shares->total(),
                    'current_page' => $shares->currentPage(),
                    'last_page' => $shares->lastPage(),
                    'per_page' => $shares->perPage(),
                ],
            ],
        ]);
    }
}

