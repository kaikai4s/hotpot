<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PointTransaction;
use App\Services\PointExpirationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PointTransactionController extends Controller
{
    public function __construct(
        private PointExpirationService $expirationService
    ) {
    }

    /**
     * 获取当前用户的积分交易记录
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        $query = PointTransaction::where('user_id', $user->id)
            ->orderBy('created_at', 'desc');

        // 类型筛选
        if ($request->has('type')) {
            $query->where('type', $request->input('type'));
        }

        // 日期范围
        if ($request->has('start_date')) {
            $query->whereDate('created_at', '>=', $request->input('start_date'));
        }
        if ($request->has('end_date')) {
            $query->whereDate('created_at', '<=', $request->input('end_date'));
        }

        $perPage = $request->input('per_page', 20);
        $transactions = $query->paginate($perPage);

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'transactions' => $transactions->items(),
                'pagination' => [
                    'total' => $transactions->total(),
                    'per_page' => $transactions->perPage(),
                    'current_page' => $transactions->currentPage(),
                    'last_page' => $transactions->lastPage(),
                ],
            ],
        ]);
    }

    /**
     * 获取即将过期的积分
     */
    public function expiring(): JsonResponse
    {
        $user = Auth::user();
        $expiringPoints = $this->expirationService->getExpiringPoints($user, 30);

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'expiring_points' => $expiringPoints,
                'total_expiring' => $this->expirationService->getExpiringPointsTotal($user, 30),
            ],
        ]);
    }
}

