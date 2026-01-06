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

        $query = Reservation::with([
            'user:id,nickname,avatar_url,equipped_title',
            'user.memberPoints',
            'table',
            'order',
        ]);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('date')) {
            $query->whereDate('date', $request->input('date'));
        }

        $page = $request->input('page', 1);
        $pageSize = $request->input('page_size', 20);
        $reservations = $query->orderBy('created_at', 'desc')->paginate($pageSize, ['*'], 'page', $page);

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

        // 统计未查看预约数量
        $unviewedCount = Reservation::where('is_viewed', false)->count();

        return response()->json([
            'code' => 200,
            'data' => [
                'reservations' => $formattedReservations,
                'pagination' => [
                    'current_page' => $reservations->currentPage(),
                    'total_pages' => $reservations->lastPage(),
                    'total_count' => $reservations->total(),
                    'page_size' => $reservations->perPage(),
                ],
                'unviewed_count' => $unviewedCount,
            ],
        ]);
    }

    /**
     * 获取预约详情
     */
    public function show(int $reservationId): JsonResponse
    {
        try {
            $reservation = Reservation::with([
                'user:id,nickname,avatar_url,equipped_title',
                'user.memberPoints',
                'table',
                'order',
            ])->findOrFail($reservationId);

            // 格式化用户信息，包含段位
            if ($reservation->user && $reservation->user->memberPoints) {
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
                'message' => '预约不存在',
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
                ->where('is_viewed', false)
                ->update([
                    'is_viewed' => true,
                    'viewed_at' => now(),
                ]);

            return response()->json([
                'code' => 200,
                'message' => "成功标记 {$count} 条预约为已查看",
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
     * 批量删除预约
     */
    public function batchDelete(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:reservations,id',
        ]);

        try {
            \Illuminate\Support\Facades\DB::beginTransaction();

            $reservations = Reservation::whereIn('id', $request->input('ids'))->get();
            $count = 0;

            foreach ($reservations as $reservation) {
                // 释放关联的桌位
                if ($reservation->table) {
                    $reservation->table->update(['status' => 'available']);
                }
                $reservation->delete();
                $count++;
            }

            \Illuminate\Support\Facades\DB::commit();

            return response()->json([
                'code' => 200,
                'message' => "成功删除 {$count} 条预约",
                'data' => [
                    'count' => $count,
                ],
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return response()->json([
                'code' => 500,
                'message' => '删除失败：' . $e->getMessage(),
            ], 500);
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

