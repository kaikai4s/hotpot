<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin\V1;

use App\Http\Controllers\Controller;
use App\Models\AchievementTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AchievementTemplateController extends Controller
{
    /**
     * 获取成就模板列表
     */
    public function index(Request $request): JsonResponse
    {
        $query = AchievementTemplate::query();

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
     * 获取成就模板详情
     */
    public function show(int $id): JsonResponse
    {
        $template = AchievementTemplate::with('rewardCoupon')->findOrFail($id);

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => ['template' => $template],
        ]);
    }

    /**
     * 创建成就模板
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'icon' => 'nullable|string|max:255',
            'category' => 'required|string|in:consume,review,invite,checkin,points',
            'target_value' => 'required|array',
            'reward_points' => 'required|integer|min:0',
            'reward_coupon_id' => 'nullable|integer|exists:coupons,id',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        $template = AchievementTemplate::create($validated);

        return response()->json([
            'code' => 200,
            'message' => '创建成功',
            'data' => ['template' => $template],
        ], 201);
    }

    /**
     * 更新成就模板
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $template = AchievementTemplate::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:100',
            'description' => 'nullable|string|max:500',
            'icon' => 'nullable|string|max:255',
            'category' => 'sometimes|string|in:consume,review,invite,checkin,points',
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
     * 删除成就模板
     */
    public function destroy(int $id): JsonResponse
    {
        $template = AchievementTemplate::findOrFail($id);

        // 检查是否有用户成就关联
        if ($template->userAchievements()->count() > 0) {
            return response()->json([
                'code' => 400,
                'message' => '该模板已有用户成就，无法删除',
            ], 400);
        }

        $template->delete();

        return response()->json([
            'code' => 200,
            'message' => '删除成功',
        ]);
    }
}

