<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin\V1;

use App\Http\Controllers\Controller;
use App\Models\Configuration;
use App\Models\Reservation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReservationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'status' => 'nullable|in:pending,confirmed,cancelled,completed,expired',
            'date' => 'nullable|date',
            'page' => 'nullable|integer|min:1',
            'page_size' => 'nullable|integer|min:1|max:100',
        ]);

        $query = Reservation::with(['user', 'table', 'order']);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('date')) {
            $query->whereDate('date', $request->input('date'));
        }

        $page = $request->input('page', 1);
        $pageSize = $request->input('page_size', 20);
        $reservations = $query->orderBy('created_at', 'desc')->paginate($pageSize, ['*'], 'page', $page);

        return response()->json([
            'code' => 200,
            'data' => [
                'reservations' => $reservations->items(),
                'pagination' => [
                    'current_page' => $reservations->currentPage(),
                    'total_pages' => $reservations->lastPage(),
                    'total_count' => $reservations->total(),
                    'page_size' => $reservations->perPage(),
                ],
            ],
        ]);
    }

    /**
     * 获取预约详情
     */
    public function show(int $reservationId): JsonResponse
    {
        try {
            $reservation = Reservation::with(['user', 'table', 'order'])
                ->findOrFail($reservationId);

            return response()->json([
                'code' => 200,
                'message' => '获取成功',
                'data' => $reservation,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 404,
                'message' => '预约不存在',
            ], 404);
        }
    }

    /**
     * 确认预约
     */
    public function confirm(int $reservationId): JsonResponse
    {
        try {
            $reservation = Reservation::findOrFail($reservationId);

            if ($reservation->status !== 'pending') {
                return response()->json([
                    'code' => 400,
                    'message' => '预约状态不允许确认',
                ], 400);
            }

            // 检查是否启用预约定金，如果启用则检查定金是否已支付
            $depositEnabled = (bool) Configuration::getValue('reservation_deposit_enabled', true);
            if ($depositEnabled && $reservation->deposit_amount > 0) {
                // 如果启用了定金且预约有定金金额，则必须支付定金才能确认
                if ($reservation->deposit_status !== 'paid') {
                    return response()->json([
                        'code' => 400,
                        'message' => '预约定金未支付，无法确认',
                    ], 400);
                }
            }
            // 如果未启用定金或定金金额为0，则不需要检查定金支付状态

            $reservation->update([
                'status' => 'confirmed',
                'confirmed_at' => now(),
            ]);

            return response()->json([
                'code' => 200,
                'message' => '预约已确认',
                'data' => $reservation,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => '确认失败：' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 取消预约
     */
    public function cancel(int $reservationId, Request $request): JsonResponse
    {
        $request->validate([
            'reason' => 'nullable|string|max:255',
        ]);

        try {
            $reservation = Reservation::with('table')->findOrFail($reservationId);

            if (!in_array($reservation->status, ['pending', 'confirmed'])) {
                return response()->json([
                    'code' => 400,
                    'message' => '预约状态不允许取消',
                ], 400);
            }

            \Illuminate\Support\Facades\DB::transaction(function () use ($reservation) {
                $reservation->update([
                    'status' => 'cancelled',
                    'cancelled_at' => now(),
                ]);

                // 释放桌位
                $reservation->refresh();
                $table = $reservation->table;
                if ($table instanceof \App\Models\Table) {
                    $table->update(['status' => 'available']);
                }
            });

            $reservation->load(['user', 'table', 'order']);

            return response()->json([
                'code' => 200,
                'message' => '预约已取消',
                'data' => $reservation,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => '取消失败：' . $e->getMessage(),
            ], 500);
        }
    }
}

