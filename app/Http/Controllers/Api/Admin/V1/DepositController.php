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
            'user_nickname' => 'nullable|string|max:64',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'page' => 'nullable|integer|min:1',
            'page_size' => 'nullable|integer|min:1|max:50',
        ]);

        try {
            $query = Reservation::with([
                'user:id,nickname,avatar_url,equipped_title',
                'user.memberPoints',
                'table',
                'order',
            ])
                ->whereNotNull('deposit_amount')
                ->where('deposit_amount', '>', 0);

            if ($request->filled('deposit_status')) {
                $query->where('deposit_status', $request->input('deposit_status'));
            }

            if ($request->filled('reservation_code')) {
                $query->where('reservation_code', 'like', '%' . $request->input('reservation_code') . '%');
            }

            // 用户昵称模糊筛选
            if ($request->filled('user_nickname')) {
                $query->whereHas('user', function ($q) use ($request) {
                    $q->where('nickname', 'like', '%' . $request->input('user_nickname') . '%');
                });
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

            // 格式化预约数据，确保用户信息包含称号和段位
            $formattedReservations = $reservations->items();
            foreach ($formattedReservations as $reservation) {
                if ($reservation->user) {
                    if (!$reservation->user->relationLoaded('memberPoints')) {
                        $reservation->user->load('memberPoints');
                    }
                    // 添加段位详细信息（包含颜色）
                    if ($reservation->user->memberPoints) {
                        $levelModel = \App\Models\PointLevel::where('code', $reservation->user->memberPoints->level)->first();
                        if ($levelModel) {
                            $reservation->user->level = [
                                'code' => $levelModel->code,
                                'name' => $levelModel->name,
                                'icon' => $levelModel->icon,
                                'color' => $levelModel->color,
                            ];
                        }
                    }
                }
            }

            // 统计未查看定金数量（只统计有定金的预约）
            $unviewedCount = Reservation::whereNotNull('deposit_amount')
                ->where('deposit_amount', '>', 0)
                ->where('is_viewed', false)
                ->count();

            return response()->json([
                'code' => 200,
                'message' => '获取成功',
                'data' => [
                    'reservations' => $formattedReservations,
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
                    'unviewed_count' => $unviewedCount,
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
     * 获取定金详情
     */
    public function show(int $reservationId): JsonResponse
    {
        try {
            $reservation = Reservation::with(['user', 'table', 'order'])
                ->whereNotNull('deposit_amount')
                ->where('deposit_amount', '>', 0)
                ->findOrFail($reservationId);

            // 标记为已查看
            if (!$reservation->is_viewed) {
                $reservation->update([
                    'is_viewed' => true,
                    'viewed_at' => now(),
                ]);
                $reservation->refresh();
            }

            return response()->json([
                'code' => 200,
                'message' => '获取成功',
                'data' => $reservation,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 404,
                'message' => '定金记录不存在',
            ], 404);
        }
    }

    /**
     * 批量标记为已查看
     */
    public function markAsViewed(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:reservations,id',
        ]);

        try {
            $count = Reservation::whereIn('id', $request->input('ids'))
                ->whereNotNull('deposit_amount')
                ->where('deposit_amount', '>', 0)
                ->where('is_viewed', false)
                ->update([
                    'is_viewed' => true,
                    'viewed_at' => now(),
                ]);

            return response()->json([
                'code' => 200,
                'message' => "成功标记 {$count} 条定金记录为已查看",
                'data' => [
                    'count' => $count,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => '操作失败：' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 批量删除定金记录
     */
    public function batchDelete(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:reservations,id',
        ]);

        try {
            DB::beginTransaction();

            $reservations = Reservation::whereIn('id', $request->input('ids'))
                ->whereNotNull('deposit_amount')
                ->where('deposit_amount', '>', 0)
                ->get();
            
            $count = 0;
            foreach ($reservations as $reservation) {
                // 如果定金已支付但未返还，不允许删除
                if ($reservation->deposit_status === 'paid' && !$reservation->deposit_refunded_at) {
                    continue;
                }
                $reservation->delete();
                $count++;
            }

            DB::commit();

            return response()->json([
                'code' => 200,
                'message' => "成功删除 {$count} 条定金记录",
                'data' => [
                    'count' => $count,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('批量删除定金记录失败', [
                'ids' => $request->input('ids'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'code' => 500,
                'message' => '删除失败：' . $e->getMessage(),
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

            // 标记为已查看
            if (!$reservation->is_viewed) {
                $reservation->update([
                    'is_viewed' => true,
                    'viewed_at' => now(),
                ]);
                $reservation->refresh();
            }

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

