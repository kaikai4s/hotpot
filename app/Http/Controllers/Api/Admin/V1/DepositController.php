<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin\V1;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Services\DepositService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DepositController extends Controller
{
    public function __construct(
        private DepositService $depositService
    ) {
    }

    /**
     * 获取定金列表
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'deposit_status' => 'nullable|in:unpaid,paid,refunded,forfeited',
            'reservation_code' => 'nullable|string|max:64',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'page' => 'nullable|integer|min:1',
            'page_size' => 'nullable|integer|min:1|max:50',
        ]);

        try {
            $query = Reservation::with(['user', 'table', 'order'])
                ->whereNotNull('deposit_amount')
                ->where('deposit_amount', '>', 0);

            if ($request->filled('deposit_status')) {
                $query->where('deposit_status', $request->input('deposit_status'));
            }

            if ($request->filled('reservation_code')) {
                $query->where('reservation_code', 'like', '%' . $request->input('reservation_code') . '%');
            }

            if ($request->filled('date_from')) {
                $query->where('created_at', '>=', $request->input('date_from') . ' 00:00:00');
            }

            if ($request->filled('date_to')) {
                $query->where('created_at', '<=', $request->input('date_to') . ' 23:59:59');
            }

            $page = $request->input('page', 1);
            $pageSize = $request->input('page_size', 20);
            $reservations = $query->orderBy('created_at', 'desc')
                ->paginate($pageSize, ['*'], 'page', $page);

            return response()->json([
                'code' => 200,
                'message' => '获取成功',
                'data' => [
                    'reservations' => $reservations->items(),
                    'pagination' => [
                        'current_page' => $reservations->currentPage(),
                        'total_pages' => $reservations->lastPage(),
                        'total_count' => $reservations->total(),
                        'page_size' => $reservations->perPage(),
                    ],
                    'statistics' => [
                        'total_amount' => (float) (Reservation::where('deposit_status', 'paid')
                            ->whereNull('deposit_refunded_at')
                            ->sum('deposit_amount') ?? 0),
                        'refunded_amount' => (float) (Reservation::where('deposit_status', 'refunded')
                            ->sum('deposit_amount') ?? 0),
                        'forfeited_amount' => (float) (Reservation::where('deposit_status', 'forfeited')
                            ->sum('deposit_amount') ?? 0),
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('获取定金列表失败', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'code' => 500,
                'message' => '获取定金列表失败：' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 手动返还定金
     */
    public function refund(int $reservationId, Request $request): JsonResponse
    {
        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            $reservation = Reservation::findOrFail($reservationId);

            if ($reservation->deposit_status !== 'paid') {
                return response()->json([
                    'code' => 400,
                    'message' => '预约定金未支付，无法返还',
                ], 400);
            }

            if ($reservation->deposit_refunded_at) {
                return response()->json([
                    'code' => 400,
                    'message' => '预约定金已返还',
                ], 400);
            }

            DB::beginTransaction();

            $this->depositService->manualRefundDeposit($reservationId, $request->input('reason'));

            DB::commit();

            $reservation->refresh();
            $reservation->load(['user', 'table', 'order']);

            return response()->json([
                'code' => 200,
                'message' => '定金返还成功',
                'data' => $reservation,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('手动返还定金失败', [
                'reservation_id' => $reservationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'code' => 500,
                'message' => '返还定金失败：' . $e->getMessage(),
            ], 500);
        }
    }
}

