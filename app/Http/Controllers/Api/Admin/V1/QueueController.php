<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin\V1;

use App\Http\Controllers\Controller;
use App\Models\Queue;
use App\Models\Configuration;
use App\Services\QueueService;
use App\Helpers\LoggerHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QueueController extends Controller
{
    public function __construct(
        private QueueService $queueService
    ) {
    }

    /**
     * 获取排队列表
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'status' => 'nullable|in:waiting,called,cancelled,seated',
            'queue_number' => 'nullable|string|max:20',
            'user_nickname' => 'nullable|string|max:64',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'page' => 'nullable|integer|min:1',
            'page_size' => 'nullable|integer|min:1|max:100',
        ]);

        $query = Queue::with(['user:id,nickname,avatar_url,phone']);

        // 状态筛选
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // 排队号搜索
        if ($request->filled('queue_number')) {
            $query->where('queue_number', 'like', '%' . $request->input('queue_number') . '%');
        }

        // 用户昵称搜索
        if ($request->filled('user_nickname')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('nickname', 'like', '%' . $request->input('user_nickname') . '%');
            });
        }

        // 日期范围筛选
        if ($request->filled('date_from')) {
            $query->whereDate('joined_at', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('joined_at', '<=', $request->input('date_to'));
        }

        // 统计信息
        $waitingCount = Queue::where('status', 'waiting')->count();
        $calledCount = Queue::where('status', 'called')->count();
        $todayCount = Queue::whereDate('joined_at', today())->count();

        $page = $request->input('page', 1);
        $pageSize = $request->input('page_size', 20);
        $queues = $query->orderBy('position', 'asc')->orderBy('joined_at', 'desc')->paginate($pageSize, ['*'], 'page', $page);

        // 获取叫号预留时间配置
        $calledTimeoutMinutes = (int) Configuration::getValue('queue_called_timeout_minutes', 15);

        // 为每个排队记录添加超时状态
        $queuesData = $queues->items();
        foreach ($queuesData as $queue) {
            // 计算是否超时（仅对已叫号状态判断）
            if ($queue->status === 'called' && $queue->called_at) {
                $calledAt = \Carbon\Carbon::parse($queue->called_at);
                $timeoutAt = $calledAt->copy()->addMinutes($calledTimeoutMinutes);
                $queue->is_timeout = now()->greaterThan($timeoutAt);
                $queue->timeout_at = $timeoutAt->toDateTimeString();
                $queue->remaining_minutes = max(0, (int) now()->diffInMinutes($timeoutAt, false));
            } else {
                $queue->is_timeout = false;
                $queue->timeout_at = null;
                $queue->remaining_minutes = null;
            }
        }

        LoggerHelper::tableInfo('获取排队列表', [
            'admin_id' => auth('admin')->id(),
            'filters' => $request->only(['status', 'queue_number', 'user_nickname', 'date_from', 'date_to']),
            'total' => $queues->total(),
        ]);

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'queues' => $queuesData,
                'pagination' => [
                    'current_page' => $queues->currentPage(),
                    'total_pages' => $queues->lastPage(),
                    'total_count' => $queues->total(),
                    'page_size' => $queues->perPage(),
                ],
                'statistics' => [
                    'waiting_count' => $waitingCount,
                    'called_count' => $calledCount,
                    'today_count' => $todayCount,
                ],
            ],
        ]);
    }

    /**
     * 获取排队详情
     */
    public function show(int $id): JsonResponse
    {
        $queue = Queue::with(['user:id,nickname,avatar_url,phone'])->findOrFail($id);

        // 获取叫号预留时间配置
        $calledTimeoutMinutes = (int) Configuration::getValue('queue_called_timeout_minutes', 15);

        // 计算是否超时（仅对已叫号状态判断）
        if ($queue->status === 'called' && $queue->called_at) {
            $calledAt = \Carbon\Carbon::parse($queue->called_at);
            $timeoutAt = $calledAt->copy()->addMinutes($calledTimeoutMinutes);
            $queue->is_timeout = now()->greaterThan($timeoutAt);
            $queue->timeout_at = $timeoutAt->toDateTimeString();
            $queue->remaining_minutes = max(0, (int) now()->diffInMinutes($timeoutAt, false));
        } else {
            $queue->is_timeout = false;
            $queue->timeout_at = null;
            $queue->remaining_minutes = null;
        }

        LoggerHelper::tableInfo('查看排队详情', [
            'admin_id' => auth('admin')->id(),
            'queue_id' => $id,
            'queue_number' => $queue->queue_number,
        ]);

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => $queue,
        ]);
    }

    /**
     * 叫号（下一个）
     */
    public function callNext(Request $request): JsonResponse
    {
        try {
            $queue = $this->queueService->callNext();

            if (!$queue) {
                return response()->json([
                    'code' => 404,
                    'message' => '当前没有等待中的排队',
                ], 404);
            }

            LoggerHelper::tableInfo('管理员叫号', [
                'admin_id' => auth('admin')->id(),
                'queue_id' => $queue->id,
                'queue_number' => $queue->queue_number,
                'user_id' => $queue->user_id,
            ]);

            return response()->json([
                'code' => 200,
                'message' => '叫号成功',
                'data' => [
                    'queue' => $queue->load('user'),
                ],
            ]);
        } catch (\Exception $e) {
            LoggerHelper::tableError('叫号失败', [
                'admin_id' => auth('admin')->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'code' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 标记为已入座
     */
    public function markSeated(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'table_id' => 'nullable|integer|exists:tables,id',
        ]);

        try {
            $queue = Queue::findOrFail($id);

            if ($queue->status !== 'called') {
                return response()->json([
                    'code' => 400,
                    'message' => '只能标记已叫号的排队为已入座',
                ], 400);
            }

            $queue->update([
                'status' => 'seated',
                'seated_at' => now(),
            ]);

            // 如果提供了桌位ID，可以关联桌位（可选功能）
            if ($request->filled('table_id')) {
                // 这里可以添加桌位关联逻辑
            }

            LoggerHelper::tableInfo('标记排队为已入座', [
                'admin_id' => auth('admin')->id(),
                'queue_id' => $id,
                'queue_number' => $queue->queue_number,
                'table_id' => $request->input('table_id'),
            ]);

            return response()->json([
                'code' => 200,
                'message' => '标记成功',
                'data' => [
                    'queue' => $queue->load('user'),
                ],
            ]);
        } catch (\Exception $e) {
            LoggerHelper::tableError('标记失败', [
                'admin_id' => auth('admin')->id(),
                'queue_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'code' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 取消排队
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        try {
            $queue = Queue::findOrFail($id);

            if ($queue->status === 'seated') {
                return response()->json([
                    'code' => 400,
                    'message' => '已入座的排队不能取消',
                ], 400);
            }

            if ($queue->status === 'cancelled') {
                return response()->json([
                    'code' => 400,
                    'message' => '该排队已取消',
                ], 400);
            }

            $queue->update([
                'status' => 'cancelled',
            ]);

            LoggerHelper::tableInfo('取消排队', [
                'admin_id' => auth('admin')->id(),
                'queue_id' => $id,
                'queue_number' => $queue->queue_number,
                'user_id' => $queue->user_id,
            ]);

            return response()->json([
                'code' => 200,
                'message' => '取消成功',
                'data' => [
                    'queue' => $queue->load('user'),
                ],
            ]);
        } catch (\Exception $e) {
            LoggerHelper::tableError('取消排队失败', [
                'admin_id' => auth('admin')->id(),
                'queue_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'code' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 批量删除
     */
    public function batchDelete(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:queue,id',
        ]);

        try {
            $ids = $request->input('ids');
            $queues = Queue::whereIn('id', $ids)->get();

            // 检查是否有不能删除的记录（已入座）
            $seatedQueues = $queues->where('status', 'seated');
            if ($seatedQueues->isNotEmpty()) {
                return response()->json([
                    'code' => 400,
                    'message' => '已入座的排队不能删除',
                    'data' => [
                        'seated_queue_numbers' => $seatedQueues->pluck('queue_number')->toArray(),
                    ],
                ], 400);
            }

            $deletedCount = Queue::whereIn('id', $ids)->delete();

            LoggerHelper::tableInfo('批量删除排队', [
                'admin_id' => auth('admin')->id(),
                'deleted_count' => $deletedCount,
                'queue_ids' => $ids,
            ]);

            return response()->json([
                'code' => 200,
                'message' => "成功删除 {$deletedCount} 条记录",
                'data' => [
                    'deleted_count' => $deletedCount,
                ],
            ]);
        } catch (\Exception $e) {
            LoggerHelper::tableError('批量删除失败', [
                'admin_id' => auth('admin')->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'code' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 调整排队位置
     */
    public function adjustPosition(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'position' => 'required|integer|min:1',
        ]);

        try {
            $queue = Queue::findOrFail($id);

            if ($queue->status !== 'waiting') {
                return response()->json([
                    'code' => 400,
                    'message' => '只能调整等待中排队的位置',
                ], 400);
            }

            $newPosition = $request->input('position');
            $oldPosition = $queue->position;

            // 如果新位置小于当前位置，需要将中间的位置后移
            if ($newPosition < $oldPosition) {
                Queue::where('status', 'waiting')
                    ->where('position', '>=', $newPosition)
                    ->where('position', '<', $oldPosition)
                    ->where('id', '!=', $id)
                    ->increment('position');
            } else {
                // 如果新位置大于当前位置，需要将中间的位置前移
                Queue::where('status', 'waiting')
                    ->where('position', '>', $oldPosition)
                    ->where('position', '<=', $newPosition)
                    ->where('id', '!=', $id)
                    ->decrement('position');
            }

            $queue->update(['position' => $newPosition]);

            LoggerHelper::tableInfo('调整排队位置', [
                'admin_id' => auth('admin')->id(),
                'queue_id' => $id,
                'queue_number' => $queue->queue_number,
                'old_position' => $oldPosition,
                'new_position' => $newPosition,
            ]);

            return response()->json([
                'code' => 200,
                'message' => '调整成功',
                'data' => [
                    'queue' => $queue->load('user'),
                ],
            ]);
        } catch (\Exception $e) {
            LoggerHelper::tableError('调整位置失败', [
                'admin_id' => auth('admin')->id(),
                'queue_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'code' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}

