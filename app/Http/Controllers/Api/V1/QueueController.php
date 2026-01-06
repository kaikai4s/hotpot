<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\QueueService;
use App\Helpers\LoggerHelper;
use App\Models\Configuration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class QueueController extends Controller
{
    public function __construct(
        private QueueService $queueService
    ) {
    }

    public function join(Request $request): JsonResponse
    {
        $request->validate([
            'guest_count' => 'required|integer|min:1|max:20',
            'table_type' => 'nullable|in:window,corner,center,any',
        ]);

        try {
            $user = Auth::user();
            $queue = $this->queueService->joinQueue(
                $user,
                $request->input('guest_count'),
                $request->input('table_type')
            );

            LoggerHelper::tableInfo('用户加入排队', [
                'user_id' => $user->id,
                'queue_id' => $queue->id,
                'queue_number' => $queue->queue_number,
                'guest_count' => $queue->guest_count,
                'table_type' => $queue->table_type,
            ]);

            $status = $this->queueService->getQueueStatus($queue->id);

            return response()->json([
                'code' => 201,
                'message' => '排队成功',
                'data' => $status,
            ], 201);
        } catch (\Exception $e) {
            LoggerHelper::tableError('加入排队失败', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);
            
            $code = $e->getCode() ?: 500;
            return response()->json([
                'code' => $code,
                'message' => $e->getMessage(),
            ], $code >= 400 && $code < 600 ? $code : 500);
        }
    }

    public function getStatus(int $queueId): JsonResponse
    {
        try {
            $queue = \App\Models\Queue::findOrFail($queueId);
            $status = $this->queueService->getQueueStatus($queueId);

            // 获取叫号预留时间配置
            $calledTimeoutMinutes = (int) Configuration::getValue('queue_called_timeout_minutes', 15);

            // 返回完整信息，与getMyQueue保持一致
            return response()->json([
                'code' => 200,
                'message' => 'success',
                'data' => array_merge($status, [
                    'guest_count' => $queue->guest_count,
                    'table_type' => $queue->table_type,
                    'joined_at' => $queue->joined_at?->toDateTimeString(),
                    'called_at' => $queue->called_at?->toDateTimeString(),
                    'seated_at' => $queue->seated_at?->toDateTimeString(),
                    'called_timeout_minutes' => $calledTimeoutMinutes,
                ]),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 404,
                'message' => '排队记录不存在',
            ], 404);
        }
    }

    /**
     * 获取当前用户的排队状态
     */
    public function getMyQueue(): JsonResponse
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'code' => 401,
                    'message' => '未登录',
                ], 401);
            }

            $queue = \App\Models\Queue::where('user_id', $user->id)
                ->whereIn('status', ['waiting', 'called'])
                ->orderBy('created_at', 'desc')
                ->first();

            if (!$queue) {
                return response()->json([
                    'code' => 200,
                    'message' => 'success',
                    'data' => null,
                ]);
            }

            $status = $this->queueService->getQueueStatus($queue->id);

            // 获取叫号预留时间配置
            $calledTimeoutMinutes = (int) Configuration::getValue('queue_called_timeout_minutes', 15);

            return response()->json([
                'code' => 200,
                'message' => 'success',
                'data' => array_merge($status, [
                    'guest_count' => $queue->guest_count,
                    'table_type' => $queue->table_type,
                    'joined_at' => $queue->joined_at?->toDateTimeString(),
                    'called_at' => $queue->called_at?->toDateTimeString(),
                    'seated_at' => $queue->seated_at?->toDateTimeString(),
                    'called_timeout_minutes' => $calledTimeoutMinutes,
                ]),
            ]);
        } catch (\Exception $e) {
            LoggerHelper::tableError('获取用户排队状态失败', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'code' => 500,
                'message' => config('app.debug') ? $e->getMessage() : '获取排队状态失败',
            ], 500);
        }
    }

    /**
     * 取消排队
     */
    public function cancel(int $queueId): JsonResponse
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'code' => 401,
                    'message' => '未登录',
                ], 401);
            }

            $queue = \App\Models\Queue::findOrFail($queueId);

            // 检查是否是当前用户的排队
            if ($queue->user_id !== $user->id) {
                return response()->json([
                    'code' => 403,
                    'message' => '无权操作',
                ], 403);
            }

            // 检查状态
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

            LoggerHelper::tableInfo('用户取消排队', [
                'user_id' => $user->id,
                'queue_id' => $queueId,
                'queue_number' => $queue->queue_number,
            ]);

            return response()->json([
                'code' => 200,
                'message' => '取消成功',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}

