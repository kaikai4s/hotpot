<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Configuration;
use App\Models\Reservation;
use App\Models\RestaurantArea;
use App\Models\User;
use App\Services\ReservationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ReservationController extends Controller
{
    public function __construct(
        private ReservationService $reservationService
    ) {
    }

    public function getAvailableTables(Request $request): JsonResponse
    {
        $request->validate([
            'date' => 'required|date',
            'time_slot' => 'required|date_format:H:i',
            'guest_count' => 'required|integer|min:1|max:20',
            'duration' => 'nullable|integer|min:60|max:300',
        ]);

        $result = $this->reservationService->getAvailableTables(
            $request->input('date'),
            $request->input('time_slot'),
            (int) $request->input('guest_count'),
            $request->input('duration') ? (int) $request->input('duration') : 120
        );

        // 获取区域配置
        $areas = RestaurantArea::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $result['areas'] = $areas;

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => $result,
        ]);
    }

    /**
     * 获取当前用户的预约列表
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'status' => 'nullable|in:pending,confirmed,cancelled,completed,expired',
            'page' => 'nullable|integer|min:1',
            'page_size' => 'nullable|integer|min:1|max:50',
        ]);

        try {
            /** @var User $user */
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'code' => 401,
                    'message' => '未登录',
                ], 401);
            }

            $query = Reservation::with(['table', 'user', 'order'])
                ->where('user_id', $user->id);

            if ($request->filled('status')) {
                $query->where('status', $request->input('status'));
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
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('获取预约列表失败', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'code' => 500,
                'message' => '获取预约列表失败：' . $e->getMessage(),
            ], 500);
        }
    }

    public function create(Request $request): JsonResponse
    {
        $request->validate([
            'table_id' => 'required|integer|exists:tables,id',
            'date' => 'required|date',
            'time_slot' => 'required|date_format:H:i',
            'guest_count' => 'required|integer|min:1|max:20',
            'contact_name' => 'required|string|max:64',
            'contact_phone' => 'required|string|max:20',
            'special_requests' => 'nullable|string|max:500',
            'idempotency_key' => 'required|string|max:64',
        ]);

        try {
            /** @var User $user */
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'code' => 401,
                    'message' => '未登录',
                ], 401);
            }

            $reservation = $this->reservationService->createReservation(
                $user,
                $request->input('table_id'),
                $request->input('date'),
                $request->input('time_slot'),
                $request->input('guest_count'),
                $request->input('contact_name'),
                $request->input('contact_phone'),
                $request->input('special_requests'),
                $request->input('idempotency_key')
            );

            $reservation->load(['table', 'user']);

            // 检查是否启用定金
            $depositEnabled = (bool) Configuration::getValue('reservation_deposit_enabled', true);
            $message = '预约成功';
            if ($depositEnabled && $reservation->deposit_amount > 0) {
                $message = '预约成功，请支付定金';
            } else {
                $message = '预约成功，我们会在15分钟内与您确认';
            }

            return response()->json([
                'code' => 201,
                'message' => $message,
                'data' => [
                    'reservation_id' => $reservation->id,
                    'reservation_code' => $reservation->reservation_code,
                    'status' => $reservation->status,
                    'table_name' => $reservation->table?->name ?? '',
                    'date' => $reservation->date->format('Y-m-d'),
                    'time_slot' => $reservation->time_slot,
                    'expires_at' => $reservation->expires_at?->format('Y-m-d H:i:s'),
                    'deposit_amount' => $reservation->deposit_amount,
                    'deposit_status' => $reservation->deposit_status,
                ],
            ], 201);
        } catch (\Exception $e) {
            $code = $e->getCode() ?: 500;
            return response()->json([
                'code' => $code,
                'message' => $e->getMessage(),
            ], $code >= 400 && $code < 600 ? $code : 500);
        }
    }

    public function confirm(int $reservationId): JsonResponse
    {
        try {
            $reservation = $this->reservationService->confirmReservation($reservationId);

            return response()->json([
                'code' => 200,
                'message' => '预约已确认',
                'data' => [
                    'reservation_id' => $reservation->id,
                    'status' => $reservation->status,
                    'confirmed_at' => $reservation->confirmed_at?->format('Y-m-d H:i:s'),
                ],
            ]);
        } catch (\Exception $e) {
            $code = $e->getCode() ?: 500;
            return response()->json([
                'code' => $code,
                'message' => $e->getMessage(),
            ], $code >= 400 && $code < 600 ? $code : 500);
        }
    }

    public function cancel(int $reservationId, Request $request): JsonResponse
    {
        $request->validate([
            'reason' => 'nullable|string|max:255',
        ]);

        try {
            $reservation = $this->reservationService->cancelReservation(
                $reservationId,
                $request->input('reason')
            );

            return response()->json([
                'code' => 200,
                'message' => '预约已取消',
                'data' => [
                    'reservation_id' => $reservation->id,
                    'status' => $reservation->status,
                    'cancelled_at' => $reservation->cancelled_at?->format('Y-m-d H:i:s'),
                ],
            ]);
        } catch (\Exception $e) {
            $code = $e->getCode() ?: 500;
            return response()->json([
                'code' => $code,
                'message' => $e->getMessage(),
            ], $code >= 400 && $code < 600 ? $code : 500);
        }
    }

    /**
     * 获取预约详情
     */
    public function show(int $reservationId): JsonResponse
    {
        try {
            $user = Auth::user();
            $reservation = Reservation::with(['table', 'user', 'order'])
                ->where('id', $reservationId)
                ->where('user_id', $user->id)
                ->firstOrFail();

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
     * 支付预约定金
     */
    public function payDeposit(int $reservationId, Request $request): JsonResponse
    {
        $request->validate([
            'payment_method' => 'required|in:wechat,mock',
        ]);

        try {
            $user = Auth::user();
            $reservation = Reservation::where('id', $reservationId)
                ->where('user_id', $user->id)
                ->firstOrFail();

            if ($reservation->deposit_status !== 'unpaid') {
                return response()->json([
                    'code' => 400,
                    'message' => '定金已支付或已处理',
                ], 400);
            }

            if ($reservation->status === 'cancelled' || $reservation->status === 'expired') {
                return response()->json([
                    'code' => 400,
                    'message' => '预约已取消或已过期，无法支付定金',
                ], 400);
            }

            $paymentMethod = $request->input('payment_method');
            $transactionId = 'DEP' . date('YmdHis') . str_pad((string) mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);

            // 模拟支付（实际应该调用支付接口）
            if ($paymentMethod === 'mock') {
                DB::transaction(function () use ($reservation, $transactionId) {
                    $reservation->update([
                        'deposit_status' => 'paid',
                        'deposit_paid_at' => now(),
                        'deposit_transaction_id' => $transactionId,
                        'deposit_data' => [
                            'method' => 'mock',
                            'paid_at' => now()->toDateTimeString(),
                        ],
                    ]);
                });

                return response()->json([
                    'code' => 200,
                    'message' => '定金支付成功',
                    'data' => [
                        'reservation_id' => $reservation->id,
                        'deposit_status' => 'paid',
                        'transaction_id' => $transactionId,
                    ],
                ]);
            }

            // 微信支付（占位）
            return response()->json([
                'code' => 200,
                'message' => '微信支付暂未实现',
                'data' => [
                    'payment_params' => [
                        'appId' => 'wx1234567890',
                        'timeStamp' => (string) time(),
                        'nonceStr' => Str::random(32),
                        'package' => 'prepay_id=wx1234567890',
                        'signType' => 'MD5',
                        'paySign' => Str::random(32),
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('支付预约定金失败', [
                'reservation_id' => $reservationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'code' => 500,
                'message' => '支付失败：' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 确认到达
     */
    public function arrive(int $reservationId): JsonResponse
    {
        try {
            $user = Auth::user();
            $reservation = Reservation::where('id', $reservationId)
                ->where('user_id', $user->id)
                ->firstOrFail();

            $reservation = $this->reservationService->markArrived($reservationId);

            return response()->json([
                'code' => 200,
                'message' => '已确认到达',
                'data' => $reservation,
            ]);
        } catch (\Exception $e) {
            $code = $e->getCode() ?: 500;
            return response()->json([
                'code' => $code,
                'message' => $e->getMessage(),
            ], $code >= 400 && $code < 600 ? $code : 500);
        }
    }
}

