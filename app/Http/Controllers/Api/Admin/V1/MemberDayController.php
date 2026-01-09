<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin\V1;

use App\Http\Controllers\Controller;
use App\Services\MemberDayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MemberDayController extends Controller
{
    public function __construct(
        private MemberDayService $memberDayService
    ) {
    }

    /**
     * 获取会员日配置
     * GET /api/admin/v1/member-day/config
     */
    public function getConfig(): JsonResponse
    {
        $config = $this->memberDayService->getConfig();

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'config' => $config,
            ],
        ]);
    }

    /**
     * 更新会员日配置
     * PUT /api/admin/v1/member-day/config
     */
    public function updateConfig(Request $request): JsonResponse
    {
        $request->validate([
            'day_of_month' => 'sometimes|integer|min:1|max:28',
            'is_enabled' => 'sometimes|boolean',
            'base_discount' => 'sometimes|numeric|min:0.5|max:1',
            'points_bonus_rate' => 'sometimes|numeric|min:0|max:2',
            'discount_by_level' => 'sometimes|array',
            'discount_by_level.bronze' => 'sometimes|numeric|min:0.5|max:1',
            'discount_by_level.silver' => 'sometimes|numeric|min:0.5|max:1',
            'discount_by_level.gold' => 'sometimes|numeric|min:0.5|max:1',
            'discount_by_level.platinum' => 'sometimes|numeric|min:0.5|max:1',
        ]);

        try {
            $config = $this->memberDayService->updateConfig($request->all());

            return response()->json([
                'code' => 200,
                'message' => '配置更新成功',
                'data' => [
                    'config' => $config,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => '配置更新失败: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 设置当月临时会员日
     * PUT /api/admin/v1/member-day/override
     */
    public function setOverride(Request $request): JsonResponse
    {
        $request->validate([
            'day' => 'nullable|integer|min:1|max:28',
        ]);

        try {
            $config = $this->memberDayService->setCurrentMonthOverride($request->input('day'));

            return response()->json([
                'code' => 200,
                'message' => $request->input('day') ? '临时会员日设置成功' : '临时会员日已清除',
                'data' => [
                    'config' => $config,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => '设置失败: ' . $e->getMessage(),
            ], 500);
        }
    }
}
