<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TaskController extends Controller
{
    public function __construct(
        private TaskService $taskService
    ) {
    }

    /**
     * 获取任务列表
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'code' => 401,
                'message' => '未登录',
            ], 401);
        }

        $type = $request->input('type'); // daily, weekly, achievement
        $tasks = $this->taskService->getUserTasks($user, $type);

        // 按类型分组
        $groupedTasks = [
            'daily' => [],
            'weekly' => [],
            'achievement' => [],
        ];

        foreach ($tasks as $task) {
            $taskType = $task['task_template']['type'] ?? 'daily';
            $groupedTasks[$taskType][] = $task;
        }

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'tasks' => $type ? ($groupedTasks[$type] ?? []) : $groupedTasks,
            ],
        ]);
    }

    /**
     * 获取任务详情
     */
    public function show(int $id): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'code' => 401,
                'message' => '未登录',
            ], 401);
        }

        $task = \App\Models\UserTask::where('id', $id)
            ->where('user_id', $user->id)
            ->with('taskTemplate')
            ->first();

        if (!$task) {
            return response()->json([
                'code' => 404,
                'message' => '任务不存在',
            ], 404);
        }

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => $task,
        ]);
    }

    /**
     * 手动完成任务（用于签到、分享等需要用户主动完成的任务）
     */
    public function complete(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'code' => 401,
                'message' => '未登录',
            ], 401);
        }

        $task = \App\Models\UserTask::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$task) {
            return response()->json([
                'code' => 404,
                'message' => '任务不存在',
            ], 404);
        }

        $completedTask = $this->taskService->completeTaskManually($user, $task->task_template_id);

        if (!$completedTask) {
            return response()->json([
                'code' => 400,
                'message' => '任务已完成或已过期',
            ], 400);
        }

        return response()->json([
            'code' => 200,
            'message' => '任务完成',
            'data' => $completedTask->load('taskTemplate'),
        ]);
    }
}

