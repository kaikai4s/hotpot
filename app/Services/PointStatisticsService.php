<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Services;

use App\Models\PointStatistic;
use App\Models\PointTransaction;
use App\Models\MemberPoint;
use Illuminate\Support\Facades\DB;

class PointStatisticsService
{
    /**
     * 计算指定日期的积分统计
     */
    public function calculateStatistics(string $date): PointStatistic
    {
        $startDate = $date . ' 00:00:00';
        $endDate = $date . ' 23:59:59';

        // 统计获得积分
        $totalEarned = PointTransaction::where('type', 'earn')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('points');

        // 统计兑换积分
        $totalRedeemed = abs(PointTransaction::where('type', 'redeem')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('points'));

        // 统计过期积分
        $totalExpired = abs(PointTransaction::where('type', 'expire')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('points'));

        // 统计活跃用户数（当日有积分变动的用户）
        $activeUsers = PointTransaction::whereBetween('created_at', [$startDate, $endDate])
            ->distinct('user_id')
            ->count('user_id');

        return PointStatistic::updateOrCreate(
            ['stat_date' => $date],
            [
                'total_earned' => $totalEarned,
                'total_redeemed' => $totalRedeemed,
                'total_expired' => $totalExpired,
                'active_users' => $activeUsers,
            ]
        );
    }

    /**
     * 获取统计报表数据
     */
    public function getStatisticsReport(array $filters = []): array
    {
        $query = PointStatistic::query();

        // 日期范围筛选
        if (isset($filters['start_date'])) {
            $query->where('stat_date', '>=', $filters['start_date']);
        }
        if (isset($filters['end_date'])) {
            $query->where('stat_date', '<=', $filters['end_date']);
        }

        $statistics = $query->orderBy('stat_date', 'desc')->get();

        // 计算汇总数据
        $summary = [
            'total_earned' => $statistics->sum('total_earned'),
            'total_redeemed' => $statistics->sum('total_redeemed'),
            'total_expired' => $statistics->sum('total_expired'),
            'total_active_users' => $statistics->sum('active_users'),
            'avg_earned_per_day' => $statistics->count() > 0 ? round($statistics->avg('total_earned'), 2) : 0,
            'avg_redeemed_per_day' => $statistics->count() > 0 ? round($statistics->avg('total_redeemed'), 2) : 0,
        ];

        return [
            'statistics' => $statistics,
            'summary' => $summary,
        ];
    }

    /**
     * 获取用户积分排行榜
     */
    public function getUserRanking(int $limit = 100): array
    {
        return MemberPoint::with('user')
            ->orderBy('total_points', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($memberPoint) {
                return [
                    'user_id' => $memberPoint->user_id,
                    'nickname' => $memberPoint->user?->nickname ?? 'N/A',
                    'avatar_url' => $memberPoint->user?->avatar_url,
                    'total_points' => $memberPoint->total_points,
                    'available_points' => $memberPoint->available_points,
                    'level' => $memberPoint->level,
                ];
            })
            ->toArray();
    }

    /**
     * 获取积分来源统计
     */
    public function getSourceStatistics(string $startDate, string $endDate): array
    {
        $transactions = PointTransaction::where('type', 'earn')
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->select('source_type', DB::raw('SUM(points) as total_points'), DB::raw('COUNT(*) as count'))
            ->groupBy('source_type')
            ->get();

        return $transactions->map(function ($item) {
            return [
                'source_type' => $item->source_type,
                'total_points' => $item->total_points,
                'count' => $item->count,
            ];
        })->toArray();
    }
}

