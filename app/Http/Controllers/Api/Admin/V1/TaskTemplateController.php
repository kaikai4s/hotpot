<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin\V1;

use App\Http\Controllers\Controller;
use App\Models\TaskTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskTemplateController extends Controller
{
    /**
     * 获取任务模板列表
     */
    public function index(Request $request): JsonResponse
    {
        $query = TaskTemplate::query();

        if ($request->has('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->has('category')) {
            $query->where('category', $request->input('category'));
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $templates = $query->orderBy('sort_order')->orderBy('id')->paginate($request->input('page_size', 20));

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'templates' => $templates->items(),
                'pagination' => [
                    'total' => $templates->total(),
                    'current_page' => $templates->currentPage(),
                    'last_page' => $templates->lastPage(),
                    'per_page' => $templates->perPage(),
                ],
            ],
        ]);
    }

    /**
     * 获取任务模板详情
     */
    public function show(int $id): JsonResponse
    {
        $template = TaskTemplate::with('rewardCoupon')->findOrFail($id);

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => ['template' => $template],
        ]);
    }

    /**
     * 创建任务模板
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'type' => 'required|string|in:daily,weekly,achievement',
            'category' => 'required|string|in:sign,review,share,order,invite,browse',
            'target_value' => 'required|array',
            'reward_points' => 'required|integer|min:0',
            'reward_coupon_id' => 'nullable|integer|exists:coupons,id',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        $template = TaskTemplate::create($validated);

        return response()->json([
            'code' => 200,
            'message' => '创建成功',
            'data' => ['template' => $template],
        ], 201);
    }

    /**
     * 更新任务模板
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $template = TaskTemplate::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:100',
            'description' => 'nullable|string|max:500',
            'type' => 'sometimes|string|in:daily,weekly,achievement',
            'category' => 'sometimes|string|in:sign,review,share,order,invite,browse',
            'target_value' => 'sometimes|array',
            'reward_points' => 'sometimes|integer|min:0',
            'reward_coupon_id' => 'nullable|integer|exists:coupons,id',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        $template->update($validated);

        return response()->json([
            'code' => 200,
            'message' => '更新成功',
            'data' => ['template' => $template->fresh()],
        ]);
    }

    /**
     * 删除任务模板
     */
    public function destroy(int $id): JsonResponse
    {
        $template = TaskTemplate::findOrFail($id);

        // 检查是否有用户任务关联
        if ($template->userTasks()->count() > 0) {
            return response()->json([
                'code' => 400,
                'message' => '该模板已有用户任务，无法删除',
            ], 400);
        }

        $template->delete();

        return response()->json([
            'code' => 200,
            'message' => '删除成功',
        ]);
    }
}

