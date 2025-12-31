<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Queue;
use App\Models\Reservation;
use App\Models\Review;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    /**
     * 获取仪表盘统计数据
     */
    public function statistics(): JsonResponse
    {
        $today = now()->startOfDay();
        $yesterday = now()->subDay()->startOfDay();

        // 今日预约数
        $todayReservations = Reservation::whereDate('date', $today->toDateString())
            ->count();
        $yesterdayReservations = Reservation::whereDate('date', $yesterday->toDateString())
            ->count();
        $reservationsGrowth = $yesterdayReservations > 0
            ? round((($todayReservations - $yesterdayReservations) / $yesterdayReservations) * 100, 1)
            : ($todayReservations > 0 ? 100 : 0);

        // 待审核评价数
        $pendingReviews = Review::where('status', 'pending')->count();

        // 当前排队数（等待中的）
        $activeQueue = Queue::where('status', 'waiting')->count();

        // 今日营业额（已支付或已完成的订单）
        // 使用 COALESCE 函数：如果 final_amount 为 null，则使用 total_amount
        $todayRevenue = Order::whereIn('status', ['paid', 'completed', 'pending_review'])
            ->whereDate('paid_at', $today->toDateString())
            ->selectRaw('COALESCE(SUM(COALESCE(final_amount, total_amount)), 0) as revenue')
            ->value('revenue') ?? 0;
        
        $yesterdayRevenue = Order::whereIn('status', ['paid', 'completed', 'pending_review'])
            ->whereDate('paid_at', $yesterday->toDateString())
            ->selectRaw('COALESCE(SUM(COALESCE(final_amount, total_amount)), 0) as revenue')
            ->value('revenue') ?? 0;
        
        $revenueGrowth = $yesterdayRevenue > 0
            ? round((($todayRevenue - $yesterdayRevenue) / $yesterdayRevenue) * 100, 1)
            : ($todayRevenue > 0 ? 100 : 0);

        // 待确认预约数
        $pendingReservations = Reservation::where('status', 'pending')->count();

        // 待处理事项
        $pendingTasks = [];
        if ($pendingReviews > 0) {
            $pendingTasks[] = [
                'id' => 'review',
                'type' => 'review',
                'title' => '待审核评价',
                'description' => "{$pendingReviews}条评价等待审核",
                'count' => $pendingReviews,
            ];
        }
        if ($pendingReservations > 0) {
            $pendingTasks[] = [
                'id' => 'reservation',
                'type' => 'reservation',
                'title' => '待确认预约',
                'description' => "{$pendingReservations}个预约需要确认",
                'count' => $pendingReservations,
            ];
        }

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'stats' => [
                    'today_reservations' => $todayReservations,
                    'reservations_growth' => $reservationsGrowth,
                    'pending_reviews' => $pendingReviews,
                    'active_queue' => $activeQueue,
                    'today_revenue' => number_format((float) $todayRevenue, 2, '.', ''),
                    'revenue_growth' => $revenueGrowth,
                ],
                'pending_tasks' => $pendingTasks,
            ],
        ]);
    }
}

