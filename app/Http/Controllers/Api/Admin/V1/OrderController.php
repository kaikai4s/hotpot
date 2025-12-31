<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    /**
     * 获取订单列表
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'status' => 'nullable|in:pending,paid,completed,cancelled',
            'payment_method' => 'nullable|in:wechat,mock',
            'order_no' => 'nullable|string|max:32',
            'user_id' => 'nullable|integer',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'page' => 'nullable|integer|min:1',
            'page_size' => 'nullable|integer|min:1|max:100',
        ]);

        $query = Order::with(['user', 'table', 'items.dish']);

        // 状态筛选（只处理非空值）
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // 支付方式筛选（只处理非空值）
        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->input('payment_method'));
        }

        // 订单号搜索（只处理非空值）
        if ($request->filled('order_no')) {
            $query->where('order_no', 'like', '%' . $request->input('order_no') . '%');
        }

        // 用户ID筛选（只处理非空值）
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        // 日期范围筛选
        if ($request->has('date_from') && $request->input('date_from')) {
            $query->where('created_at', '>=', $request->input('date_from') . ' 00:00:00');
        }
        if ($request->has('date_to') && $request->input('date_to')) {
            $query->where('created_at', '<=', $request->input('date_to') . ' 23:59:59');
        }

        $page = $request->input('page', 1);
        $pageSize = $request->input('page_size', 20);
        $orders = $query->orderBy('created_at', 'desc')->paginate($pageSize, ['*'], 'page', $page);

        return response()->json([
            'code' => 200,
            'data' => [
                'orders' => $orders->items(),
                'pagination' => [
                    'current_page' => $orders->currentPage(),
                    'total_pages' => $orders->lastPage(),
                    'total_count' => $orders->total(),
                    'page_size' => $orders->perPage(),
                ],
            ],
        ]);
    }

    /**
     * 获取订单详情
     */
    public function show(int $id): JsonResponse
    {
        $order = Order::with(['user', 'table', 'items.dish'])
            ->find($id);

        if (!$order) {
            return response()->json([
                'code' => 404,
                'message' => '订单不存在',
            ], 404);
        }

        return response()->json([
            'code' => 200,
            'data' => $order,
        ]);
    }

    /**
     * 更新订单状态
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'status' => [
                'required',
                Rule::in(['pending', 'paid', 'pending_review', 'completed', 'cancelled']),
            ],
        ]);

        $order = Order::find($id);

        if (!$order) {
            return response()->json([
                'code' => 404,
                'message' => '订单不存在',
            ], 404);
        }

        $oldStatus = $order->status;
        $newStatus = $request->input('status');

        // 状态转换验证
        $allowedTransitions = [
            'pending' => ['paid', 'pending_review', 'cancelled'],
            'paid' => ['pending_review', 'completed', 'cancelled'],
            'pending_review' => ['completed', 'cancelled'],
            'completed' => [],
            'cancelled' => [],
        ];

        if (!in_array($newStatus, $allowedTransitions[$oldStatus] ?? [])) {
            return response()->json([
                'code' => 400,
                'message' => "无法从 {$oldStatus} 状态转换为 {$newStatus} 状态",
            ], 400);
        }

        try {
            DB::beginTransaction();

            $updateData = ['status' => $newStatus];

            // 根据状态设置相应的时间戳
            if ($newStatus === 'completed' && !$order->completed_at) {
                $updateData['completed_at'] = now();
            }

            $order->update($updateData);

            DB::commit();

            Log::info('订单状态更新', [
                'order_id' => $order->id,
                'order_no' => $order->order_no,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
            ]);

            $order->refresh();
            $order->load(['user', 'table', 'items.dish']);

            return response()->json([
                'code' => 200,
                'message' => '订单状态更新成功',
                'data' => $order,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('更新订单状态失败', [
                'order_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'code' => 500,
                'message' => '更新订单状态失败：' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 取消订单
     */
    public function cancel(int $id): JsonResponse
    {
        $order = Order::find($id);

        if (!$order) {
            return response()->json([
                'code' => 404,
                'message' => '订单不存在',
            ], 404);
        }

        if ($order->status === 'completed') {
            return response()->json([
                'code' => 400,
                'message' => '已完成订单无法取消',
            ], 400);
        }

        if ($order->status === 'cancelled') {
            return response()->json([
                'code' => 400,
                'message' => '订单已取消',
            ], 400);
        }

        try {
            DB::beginTransaction();

            $order->update([
                'status' => 'cancelled',
            ]);

            DB::commit();

            Log::info('订单已取消', [
                'order_id' => $order->id,
                'order_no' => $order->order_no,
            ]);

            $order->refresh();
            $order->load(['user', 'table', 'items.dish']);

            return response()->json([
                'code' => 200,
                'message' => '订单已取消',
                'data' => $order,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('取消订单失败', [
                'order_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'code' => 500,
                'message' => '取消订单失败：' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 完成订单
     */
    public function complete(int $id): JsonResponse
    {
        $order = Order::find($id);

        if (!$order) {
            return response()->json([
                'code' => 404,
                'message' => '订单不存在',
            ], 404);
        }

        if ($order->status !== 'paid') {
            return response()->json([
                'code' => 400,
                'message' => '只有已支付订单才能完成',
            ], 400);
        }

        try {
            DB::beginTransaction();

            $order->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            DB::commit();

            Log::info('订单已完成', [
                'order_id' => $order->id,
                'order_no' => $order->order_no,
            ]);

            $order->refresh();
            $order->load(['user', 'table', 'items.dish']);

            return response()->json([
                'code' => 200,
                'message' => '订单已完成',
                'data' => $order,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('完成订单失败', [
                'order_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'code' => 500,
                'message' => '完成订单失败：' . $e->getMessage(),
            ], 500);
        }
    }
}

