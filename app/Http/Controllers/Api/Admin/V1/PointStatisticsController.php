<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin\V1;

use App\Http\Controllers\Controller;
use App\Services\PointStatisticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PointStatisticsController extends Controller
{
    public function __construct(
        private PointStatisticsService $statisticsService
    ) {
    }

    /**
     * 获取积分统计报表
     */
    public function report(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $filters = [];
        if ($request->has('start_date')) {
            $filters['start_date'] = $request->input('start_date');
        }
        if ($request->has('end_date')) {
            $filters['end_date'] = $request->input('end_date');
        }

        $result = $this->statisticsService->getStatisticsReport($filters);

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => $result,
        ]);
    }

    /**
     * 获取用户积分排行榜
     */
    public function ranking(Request $request): JsonResponse
    {
        $limit = $request->input('limit', 100);
        $ranking = $this->statisticsService->getUserRanking((int) $limit);

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'ranking' => $ranking,
            ],
        ]);
    }

    /**
     * 获取积分来源统计
     */
    public function sourceStatistics(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $statistics = $this->statisticsService->getSourceStatistics(
            $request->input('start_date'),
            $request->input('end_date')
        );

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'statistics' => $statistics,
            ],
        ]);
    }
}

