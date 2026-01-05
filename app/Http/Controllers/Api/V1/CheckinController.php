<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\CheckinService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckinController extends Controller
{
    public function __construct(
        private CheckinService $checkinService
    ) {
    }

    /**
     * 每日签到
     */
    public function checkin(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'code' => 401,
                'message' => '未登录',
            ], 401);
        }

        try {
            $checkin = $this->checkinService->checkin($user);

            return response()->json([
                'code' => 200,
                'message' => '签到成功',
                'data' => [
                    'checkin_date' => $checkin->checkin_date->format('Y-m-d'),
                    'consecutive_days' => $checkin->consecutive_days,
                    'reward_points' => $checkin->reward_points,
                    'is_makeup' => $checkin->is_makeup,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 400,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * 获取签到统计
     */
    public function stat(): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'code' => 401,
                'message' => '未登录',
            ], 401);
        }

        $stat = $this->checkinService->getCheckinStat($user);

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => $stat,
        ]);
    }

    /**
     * 获取签到日历
     */
    public function calendar(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'code' => 401,
                'message' => '未登录',
            ], 401);
        }

        $year = (int) $request->input('year', now()->year);
        $month = (int) $request->input('month', now()->month);

        // 验证年月范围
        if ($year < 2020 || $year > 2100 || $month < 1 || $month > 12) {
            return response()->json([
                'code' => 400,
                'message' => '无效的年月参数',
            ], 400);
        }

        $calendar = $this->checkinService->getCheckinCalendar($user, $year, $month);

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => $calendar,
        ]);
    }

    /**
     * 补签
     */
    public function makeup(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'code' => 401,
                'message' => '未登录',
            ], 401);
        }

        $request->validate([
            'date' => 'required|date|before:today',
        ]);

        try {
            $date = Carbon::parse($request->input('date'));
            $checkin = $this->checkinService->makeupCheckin($user, $date);

            return response()->json([
                'code' => 200,
                'message' => '补签成功',
                'data' => [
                    'checkin_date' => $checkin->checkin_date->format('Y-m-d'),
                    'consecutive_days' => $checkin->consecutive_days,
                    'reward_points' => $checkin->reward_points,
                    'is_makeup' => $checkin->is_makeup,
                    'cost_points' => 50, // 补签消耗的积分
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 400,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}

