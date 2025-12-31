<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin\V1;

use App\Http\Controllers\Controller;
use App\Services\PointAnomalyDetectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PointAnomalyController extends Controller
{
    public function __construct(
        private PointAnomalyDetectionService $anomalyService
    ) {
    }

    /**
     * 获取积分异常列表
     */
    public function index(Request $request): JsonResponse
    {
        $options = [
            'large_earn_threshold' => $request->input('large_earn_threshold', 10000),
            'max_transactions_per_hour' => $request->input('max_transactions_per_hour', 50),
            'time_window_hours' => $request->input('time_window_hours', 24),
            'expiration_threshold' => $request->input('expiration_threshold', 0.3),
        ];

        $anomalies = $this->anomalyService->detectAnomalies($options);

        // 按严重程度排序
        usort($anomalies, function ($a, $b) {
            $severityOrder = ['high' => 1, 'medium' => 2, 'low' => 3];
            return $severityOrder[$a['severity']] <=> $severityOrder[$b['severity']];
        });

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'anomalies' => $anomalies,
                'total' => count($anomalies),
            ],
        ]);
    }

    /**
     * 获取异常统计摘要
     */
    public function summary(): JsonResponse
    {
        $summary = $this->anomalyService->getAnomalySummary();

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'summary' => $summary,
            ],
        ]);
    }
}

