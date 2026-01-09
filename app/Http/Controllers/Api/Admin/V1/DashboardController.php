<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin\V1;

use App\Http\Controllers\Controller;
use App\Models\Dish;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Queue;
use App\Models\Reservation;
use App\Models\Review;
use App\Models\Table;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * 获取仪表盘统计数据
     */
    public function statistics(): JsonResponse
    {
        $today = now()->startOfDay();
        $yesterday = now()->subDay()->startOfDay();
        $weekAgo = now()->subWeek()->startOfDay();

        // 今日预约数
        $todayReservations = Reservation::whereDate('date', $today->toDateString())
            ->count();
        $yesterdayReservations = Reservation::whereDate('date', $yesterday->toDateString())
            ->count();
        $reservationsGrowth = $yesterdayReservations > 0
            ? round((($todayReservations - $yesterdayReservations) / $yesterdayReservations) * 100, 1)
            : ($todayReservations > 0 ? 100 : 0);

        // 今日订单数
        $todayOrders = Order::whereDate('created_at', $today->toDateString())->count();
        $yesterdayOrders = Order::whereDate('created_at', $yesterday->toDateString())->count();
        $ordersGrowth = $yesterdayOrders > 0
            ? round((($todayOrders - $yesterdayOrders) / $yesterdayOrders) * 100, 1)
            : ($todayOrders > 0 ? 100 : 0);

        // 待审核评价数
        $pendingReviews = Review::where('status', 'pending')->count();

        // 当前排队数（等待中的）
        $activeQueue = Queue::where('status', 'waiting')->count();

        // 今日营业额（已支付或已完成的订单）
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

        // 用户统计
        $totalUsers = User::count();
        $todayNewUsers = User::whereDate('created_at', $today->toDateString())->count();

        // 菜品统计
        $totalDishes = Dish::count();
        $activeDishes = Dish::where('status', 'available')->count();

        // 桌位统计
        $totalTables = Table::count();
        $occupiedTables = Table::where('status', 'occupied')->count();
        $tableUsageRate = $totalTables > 0 ? round(($occupiedTables / $totalTables) * 100, 0) : 0;

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

        // 热销菜品排行（本周）
        $topDishes = OrderItem::select('dish_id', DB::raw('SUM(quantity) as sales_count'))
            ->whereHas('order', function ($query) use ($weekAgo) {
                $query->whereIn('status', ['paid', 'completed', 'pending_review'])
                    ->where('created_at', '>=', $weekAgo);
            })
            ->groupBy('dish_id')
            ->orderByDesc('sales_count')
            ->limit(5)
            ->with('dish:id,name')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->dish_id,
                    'name' => $item->dish?->name ?? '未知菜品',
                    'sales_count' => (int) $item->sales_count,
                ];
            });

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'stats' => [
                    'today_reservations' => $todayReservations,
                    'reservations_growth' => $reservationsGrowth,
                    'today_orders' => $todayOrders,
                    'orders_growth' => $ordersGrowth,
                    'pending_reviews' => $pendingReviews,
                    'active_queue' => $activeQueue,
                    'today_revenue' => number_format((float) $todayRevenue, 2, '.', ''),
                    'revenue_growth' => $revenueGrowth,
                    'total_users' => $totalUsers,
                    'today_new_users' => $todayNewUsers,
                    'total_dishes' => $totalDishes,
                    'active_dishes' => $activeDishes,
                    'total_tables' => $totalTables,
                    'occupied_tables' => $occupiedTables,
                    'table_usage_rate' => $tableUsageRate,
                ],
                'pending_tasks' => $pendingTasks,
                'top_dishes' => $topDishes,
            ],
        ]);
    }
}

