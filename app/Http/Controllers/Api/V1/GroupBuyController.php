<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\GroupBuy;
use App\Models\GroupBuyOrder;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GroupBuyController extends Controller
{
    /**
     * 获取团购列表（前台）
     */
    public function index(Request $request): JsonResponse
    {
        $query = GroupBuy::with(['items.dish'])
            ->where('is_active', true)
            ->whereIn('status', ['published', 'ongoing']);

        // 只显示进行中的团购
        $now = now();
        $query->where(function ($q) use ($now) {
            $q->whereNull('start_time')
              ->orWhere('start_time', '<=', $now);
        })->where(function ($q) use ($now) {
            $q->whereNull('end_time')
              ->orWhere('end_time', '>=', $now);
        });

        // 排序
        $query->orderBy('sort_order', 'asc')
              ->orderBy('created_at', 'desc');

        $perPage = $request->input('per_page', 20);
        $groupBuys = $query->paginate($perPage);

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'group_buys' => $groupBuys->items(),
                'pagination' => [
                    'total' => $groupBuys->total(),
                    'per_page' => $groupBuys->perPage(),
                    'current_page' => $groupBuys->currentPage(),
                    'last_page' => $groupBuys->lastPage(),
                ],
            ],
        ]);
    }

    /**
     * 获取团购详情（前台）
     */
    public function show(int $id): JsonResponse
    {
        $groupBuy = GroupBuy::with(['items.dish'])->findOrFail($id);

        if (!$groupBuy->isAvailable()) {
            return response()->json([
                'code' => 404,
                'message' => '套餐不存在或已结束',
            ], 404);
        }

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'group_buy' => $groupBuy,
            ],
        ]);
    }

    /**
     * 购买团购
     */
    public function purchase(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'code' => 401,
                'message' => '未登录',
            ], 401);
        }

        $request->validate([
            'quantity' => 'required|integer|min:1',
            'payment_method' => 'required|in:wechat,mock',
        ]);

        $groupBuy = GroupBuy::findOrFail($id);

        if (!$groupBuy->isAvailable()) {
            return response()->json([
                'code' => 400,
                'message' => '套餐不可用',
            ], 400);
        }

        $quantity = $request->input('quantity');

        // 检查库存
        if ($groupBuy->stock > 0 && ($groupBuy->sold_count + $quantity) > $groupBuy->stock) {
            return response()->json([
                'code' => 400,
                'message' => '库存不足',
            ], 400);
        }

        // 检查限购
        if ($groupBuy->limit_per_user > 0) {
            $userPurchasedCount = GroupBuyOrder::where('group_buy_id', $id)
                ->where('user_id', $user->id)
                ->whereIn('status', ['paid', 'used'])
                ->sum('quantity');

            if (($userPurchasedCount + $quantity) > $groupBuy->limit_per_user) {
                return response()->json([
                    'code' => 400,
                    'message' => "每人限购 {$groupBuy->limit_per_user} 份，您已购买 {$userPurchasedCount} 份",
                ], 400);
            }
        }

        try {
            DB::beginTransaction();

            // 计算总价
            $totalPrice = $groupBuy->group_price * $quantity;

            // 创建团购订单
            $groupBuyOrder = GroupBuyOrder::create([
                'group_buy_id' => $groupBuy->id,
                'user_id' => $user->id,
                'quantity' => $quantity,
                'total_price' => $totalPrice,
                'status' => 'pending',
                'expires_at' => $groupBuy->valid_days > 0 
                    ? now()->addDays($groupBuy->valid_days) 
                    : ($groupBuy->valid_to ?? null),
            ]);

            // 创建普通订单（用于支付）
            $orderNo = 'GB' . date('YmdHis') . str_pad((string) mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
            
            $order = Order::create([
                'order_no' => $orderNo,
                'user_id' => $user->id,
                'total_amount' => $totalPrice,
                'final_amount' => $totalPrice,
                'status' => 'pending',
                'payment_method' => $request->input('payment_method'),
            ]);

            // 关联订单
            $groupBuyOrder->update(['order_id' => $order->id]);

            // 更新已售数量
            $groupBuy->increment('sold_count', $quantity);

            DB::commit();

            Log::info('套餐购买成功', [
                'group_buy_id' => $id,
                'user_id' => $user->id,
                'quantity' => $quantity,
                'order_id' => $order->id,
            ]);

            return response()->json([
                'code' => 200,
                'message' => '套餐购买成功，请完成支付',
                'data' => [
                    'group_buy_order' => $groupBuyOrder->fresh(['groupBuy', 'user']),
                    'order' => $order,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('套餐购买失败', [
                'group_buy_id' => $id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'code' => 500,
                'message' => '套餐购买失败：' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 获取用户的团购订单列表
     */
    public function myOrders(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'code' => 401,
                'message' => '未登录',
            ], 401);
        }

        $query = GroupBuyOrder::with(['groupBuy.items.dish', 'order'])
            ->where('user_id', $user->id);

        // 状态筛选
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        $query->orderBy('created_at', 'desc');

        $perPage = $request->input('per_page', 20);
        $orders = $query->paginate($perPage);

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'orders' => $orders->items(),
                'pagination' => [
                    'total' => $orders->total(),
                    'per_page' => $orders->perPage(),
                    'current_page' => $orders->currentPage(),
                    'last_page' => $orders->lastPage(),
                ],
            ],
        ]);
    }

    /**
     * 获取用户的团购订单详情
     */
    public function myOrder(int $id): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'code' => 401,
                'message' => '未登录',
            ], 401);
        }

        $order = GroupBuyOrder::with(['groupBuy.items.dish', 'order'])
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'order' => $order,
            ],
        ]);
    }
}
