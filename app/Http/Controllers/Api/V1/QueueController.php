<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\QueueService;
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

            return response()->json([
                'code' => 201,
                'message' => '排队成功',
                'data' => [
                    'queue_id' => $queue->id,
                    'queue_number' => $queue->queue_number,
                    'current_position' => $queue->position,
                    'estimated_wait_time' => 30, // 简化处理
                    'status' => $queue->status,
                ],
            ], 201);
        } catch (\Exception $e) {
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
            $status = $this->queueService->getQueueStatus($queueId);

            return response()->json([
                'code' => 200,
                'message' => 'success',
                'data' => $status,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 404,
                'message' => '排队记录不存在',
            ], 404);
        }
    }
}

