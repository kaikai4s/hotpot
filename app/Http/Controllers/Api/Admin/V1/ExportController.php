<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Reservation;
use App\Models\Review;
use App\Models\User;
use App\Models\Dish;
use App\Models\Queue;
use App\Services\ExportService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ExportController extends Controller
{
    public function __construct(
        private ExportService $exportService
    ) {
    }

    /**
     * 导出订单数据
     */
    public function orders(Request $request): Response
    {
        $query = Order::with(['user', 'table']);

        // 应用筛选条件
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $orders = $query->orderBy('created_at', 'desc')->get();

        $headers = ['订单号', '用户', '桌位', '订单金额', '实付金额', '支付方式', '状态', '支付时间', '创建时间'];
        $fields = ['order_no', 'user.nickname', 'table.name', 'total_amount', 'final_amount', 'payment_method', 'status', 'paid_at', 'created_at'];

        // 状态映射
        $orders = $orders->map(function ($order) {
            $statusMap = [
                'pending' => '待支付',
                'paid' => '已支付',
                'pending_review' => '待评价',
                'completed' => '已完成',
                'cancelled' => '已取消',
            ];
            $paymentMap = [
                'wechat' => '微信支付',
                'mock' => '模拟支付',
            ];
            $order->status = $statusMap[$order->status] ?? $order->status;
            $order->payment_method = $paymentMap[$order->payment_method] ?? $order->payment_method ?? '未支付';
            return $order;
        });

        $csv = $this->exportService->exportToCsv($orders, $headers, $fields, 'orders');

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="orders_' . date('Y-m-d_His') . '.csv"',
        ]);
    }

    /**
     * 导出预约数据
     */
    public function reservations(Request $request): Response
    {
        $query = Reservation::with(['user', 'table']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }

        $reservations = $query->orderBy('date', 'desc')->orderBy('time_slot', 'desc')->get();

        $headers = ['预约码', '用户', '联系电话', '桌位', '人数', '日期', '时间', '状态', '备注', '创建时间'];
        $fields = ['reservation_code', 'user.nickname', 'contact_phone', 'table.name', 'guest_count', 'date', 'time_slot', 'status', 'special_requests', 'created_at'];

        $reservations = $reservations->map(function ($reservation) {
            $statusMap = [
                'pending' => '待确认',
                'confirmed' => '已确认',
                'cancelled' => '已取消',
                'completed' => '已完成',
                'expired' => '已过期',
            ];
            $reservation->status = $statusMap[$reservation->status] ?? $reservation->status;
            return $reservation;
        });

        $csv = $this->exportService->exportToCsv($reservations, $headers, $fields, 'reservations');

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="reservations_' . date('Y-m-d_His') . '.csv"',
        ]);
    }

    /**
     * 导出用户数据
     */
    public function users(Request $request): Response
    {
        $query = User::with(['memberPoints']);

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active === 'true');
        }

        $users = $query->orderBy('created_at', 'desc')->get();

        $headers = ['ID', '昵称', '手机号', '性别', '积分', '状态', '注册时间'];
        $fields = ['id', 'nickname', 'phone', 'gender', 'memberPoints.points', 'is_active', 'created_at'];

        $users = $users->map(function ($user) {
            $genderMap = [0 => '未知', 1 => '男', 2 => '女'];
            $user->gender = $genderMap[$user->gender] ?? '未知';
            $user->is_active = $user->is_active ? '正常' : '禁用';
            return $user;
        });

        $csv = $this->exportService->exportToCsv($users, $headers, $fields, 'users');

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="users_' . date('Y-m-d_His') . '.csv"',
        ]);
    }

    /**
     * 导出评价数据
     */
    public function reviews(Request $request): Response
    {
        $query = Review::with(['user', 'order']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $reviews = $query->orderBy('created_at', 'desc')->get();

        $headers = ['ID', '用户', '订单号', '评分', '内容', '状态', '创建时间'];
        $fields = ['id', 'user.nickname', 'order.order_no', 'rating', 'content', 'status', 'created_at'];

        $reviews = $reviews->map(function ($review) {
            $statusMap = [
                'pending' => '待审核',
                'approved' => '已通过',
                'rejected' => '已拒绝',
            ];
            $review->status = $statusMap[$review->status] ?? $review->status;
            return $review;
        });

        $csv = $this->exportService->exportToCsv($reviews, $headers, $fields, 'reviews');

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="reviews_' . date('Y-m-d_His') . '.csv"',
        ]);
    }

    /**
     * 导出菜品数据
     */
    public function dishes(Request $request): Response
    {
        $query = Dish::with(['category']);

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $dishes = $query->orderBy('sort_order')->get();

        $headers = ['ID', '名称', '分类', '价格', '描述', '状态', '排序', '创建时间'];
        $fields = ['id', 'name', 'category.name', 'price', 'description', 'status', 'sort_order', 'created_at'];

        $dishes = $dishes->map(function ($dish) {
            $statusMap = [
                'available' => '上架',
                'unavailable' => '下架',
                'sold_out' => '售罄',
            ];
            $dish->status = $statusMap[$dish->status] ?? $dish->status;
            return $dish;
        });

        $csv = $this->exportService->exportToCsv($dishes, $headers, $fields, 'dishes');

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="dishes_' . date('Y-m-d_His') . '.csv"',
        ]);
    }

    /**
     * 导出排队数据
     */
    public function queues(Request $request): Response
    {
        $query = Queue::with(['user']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $queues = $query->orderBy('created_at', 'desc')->get();

        $headers = ['排队号', '用户', '人数', '桌位偏好', '状态', '加入时间', '叫号时间', '入座时间'];
        $fields = ['queue_number', 'user.nickname', 'guest_count', 'table_type', 'status', 'joined_at', 'called_at', 'seated_at'];

        $queues = $queues->map(function ($queue) {
            $statusMap = [
                'waiting' => '等待中',
                'called' => '已叫号',
                'seated' => '已入座',
                'cancelled' => '已取消',
            ];
            $tableTypeMap = [
                'window' => '窗边',
                'corner' => '角落',
                'center' => '中央',
                'any' => '任意',
            ];
            $queue->status = $statusMap[$queue->status] ?? $queue->status;
            $queue->table_type = $tableTypeMap[$queue->table_type] ?? $queue->table_type ?? '不限';
            return $queue;
        });

        $csv = $this->exportService->exportToCsv($queues, $headers, $fields, 'queues');

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="queues_' . date('Y-m-d_His') . '.csv"',
        ]);
    }
}
