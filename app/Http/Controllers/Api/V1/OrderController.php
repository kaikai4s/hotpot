<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Combo;
use App\Models\Configuration;
use App\Models\Dish;
use App\Models\MemberPoint;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PointLevel;
use App\Models\Reservation;
use App\Services\PointService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function __construct(
        private PointService $pointService
    ) {
    }

    /**
     * 创建订单
     */
    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'items' => 'required|array|min:1',
            'items.*.type' => 'required|in:dish,combo',
            'items.*.dish_id' => 'required_if:items.*.type,dish|integer|exists:dishes,id',
            'items.*.combo_id' => 'required_if:items.*.type,combo|integer|exists:combos,id',
            'items.*.quantity' => 'required|integer|min:1',
            'table_id' => 'nullable|integer|exists:tables,id',
            'reservation_id' => 'nullable|integer|exists:reservations,id',
            'use_deposit' => 'nullable|boolean',
            'use_points' => 'nullable|boolean',
            'points_used' => 'nullable|integer|min:0',
            'user_coupon_id' => 'nullable|integer|exists:user_coupons,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'code' => 400,
                'message' => '参数验证失败',
                'errors' => $validator->errors(),
            ], 400);
        }

        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'code' => 401,
                'message' => '未登录',
            ], 401);
        }

        $items = $request->input('items');
        $tableId = $request->input('table_id');
        $reservationId = $request->input('reservation_id');
        $useDeposit = $request->boolean('use_deposit', false);
        $usePoints = $request->boolean('use_points', false);
        $pointsUsed = (int) $request->input('points_used', 0);
        $userCouponId = $request->input('user_coupon_id');

        try {
            DB::beginTransaction();

            // 验证预约（如果提供了预约ID）
            $reservation = null;
            $depositDiscount = 0;
            if ($reservationId) {
                $reservation = Reservation::where('id', $reservationId)
                    ->where('user_id', $user->id)
                    ->first();

                if (!$reservation) {
                    DB::rollBack();
                    return response()->json([
                        'code' => 404,
                        'message' => '预约不存在',
                    ], 404);
                }

                // 检查预约是否可以用于抵扣（已确认且已到达）
                if ($useDeposit && $reservation->status === 'confirmed' && $reservation->arrived_at && $reservation->deposit_status === 'paid') {
                    $depositDiscount = $reservation->deposit_amount ?? 0;
                }
            }

            // 验证积分抵扣
            $pointsDiscount = 0;
            if ($usePoints && $pointsUsed > 0) {
                /** @var \App\Models\User $user */
                $memberPoint = $this->pointService->getPoints($user);
                if ($memberPoint->available_points < $pointsUsed) {
                    DB::rollBack();
                    return response()->json([
                        'code' => 400,
                        'message' => '积分不足',
                    ], 400);
                }

                // 获取积分抵扣比例（多少积分=1元）
                $pointsToMoneyRate = (int) Configuration::getValue('points_to_money_rate', 100);
                $pointsDiscount = min($pointsUsed / $pointsToMoneyRate, $memberPoint->available_points / $pointsToMoneyRate);
            }

            // 验证优惠券
            $couponDiscount = 0;
            $userCoupon = null;
            if ($userCouponId) {
                // 创建订单时，优惠券必须是未使用的
                $userCoupon = \App\Models\UserCoupon::where('id', $userCouponId)
                    ->where('user_id', $user->id)
                    ->where('status', 'unused')
                    ->with(['coupon.dish'])
                    ->first();

                if (!$userCoupon) {
                    DB::rollBack();
                    return response()->json([
                        'code' => 400,
                        'message' => '优惠券不存在或已使用',
                    ], 400);
                }

                $coupon = $userCoupon->coupon;
                if (!$coupon || !$coupon->isUsable()) {
                    DB::rollBack();
                    return response()->json([
                        'code' => 400,
                        'message' => '优惠券不可用',
                    ], 400);
                }
            }

            // 验证菜品/套餐并计算总金额
            $totalAmount = 0;
            $orderItems = [];

            foreach ($items as $item) {
                $type = $item['type'] ?? 'dish';
                $quantity = (int) $item['quantity'];

                if ($type === 'combo') {
                    // 处理套餐
                    $combo = Combo::with('dishes.dish')->find($item['combo_id']);
                    if (!$combo) {
                        DB::rollBack();
                        return response()->json([
                            'code' => 404,
                            'message' => "套餐 ID {$item['combo_id']} 不存在",
                        ], 404);
                    }

                    if (!$combo->isAvailable()) {
                        DB::rollBack();
                        return response()->json([
                            'code' => 400,
                            'message' => "套餐 {$combo->name} 暂不可用",
                        ], 400);
                    }

                    // 计算原价总计和优惠金额
                    $originalTotal = $combo->original_total;
                    $savings = $combo->savings;
                    $subtotal = $combo->price * $quantity;
                    $totalAmount += $subtotal;

                    $orderItems[] = [
                        'type' => 'combo',
                        'combo' => $combo,
                        'quantity' => $quantity,
                        'price' => $combo->price,
                        'subtotal' => $subtotal,
                        'original_total' => $originalTotal * $quantity,
                        'savings' => $savings * $quantity,
                    ];
                } else {
                    // 处理菜品
                    $dish = Dish::find($item['dish_id']);
                    if (!$dish) {
                        DB::rollBack();
                        return response()->json([
                            'code' => 404,
                            'message' => "菜品 ID {$item['dish_id']} 不存在",
                        ], 404);
                    }

                    if ($dish->status !== 'available') {
                        DB::rollBack();
                        return response()->json([
                            'code' => 400,
                            'message' => "菜品 {$dish->name} 暂不可用",
                        ], 400);
                    }

                    $subtotal = $dish->price * $quantity;
                    $totalAmount += $subtotal;

                    $orderItems[] = [
                        'type' => 'dish',
                        'dish' => $dish,
                        'quantity' => $quantity,
                        'price' => $dish->price,
                        'subtotal' => $subtotal,
                    ];
                }
            }

            // 计算优惠券折扣（需要在总金额计算之后）
            if ($userCoupon && $coupon) {
                // 确保 totalAmount 是 float 类型
                $couponDiscount = $coupon->calculateDiscount((float) $totalAmount);
                
                // 如果优惠券是兑换菜品类型，需要检查订单中是否包含该菜品
                if ($coupon->type === 'dish_exchange' && $coupon->dish_id) {
                    $hasDish = false;
                    foreach ($orderItems as $item) {
                        // 检查菜品订单项或套餐中包含的菜品
                        if (isset($item['type']) && $item['type'] === 'dish' && $item['dish']->id === $coupon->dish_id) {
                            $hasDish = true;
                            break;
                        } elseif (isset($item['type']) && $item['type'] === 'combo') {
                            foreach ($item['combo']->dishes as $comboDish) {
                                if ($comboDish->dish_id === $coupon->dish_id) {
                                    $hasDish = true;
                                    break 2;
                                }
                            }
                        } elseif (!isset($item['type']) && isset($item['dish']) && $item['dish']->id === $coupon->dish_id) {
                            // 兼容旧代码（没有type字段的情况）
                            $hasDish = true;
                            break;
                        }
                    }
                    
                    if (!$hasDish) {
                        DB::rollBack();
                        return response()->json([
                            'code' => 400,
                            'message' => '订单中不包含该优惠券可兑换的菜品',
                        ], 400);
                    }
                }
            }

            // 计算段位折扣
            $levelDiscount = 0;
            $userLevelCode = null;
            $memberPoint = $this->pointService->getPoints($user);
            if ($memberPoint && $memberPoint->level) {
                $userLevel = PointLevel::where('code', $memberPoint->level)
                    ->where('is_active', true)
                    ->first();
                
                if ($userLevel) {
                    $userLevelCode = $userLevel->code;
                    // 段位折扣基于订单总金额（在优惠券折扣之前）
                    $levelDiscount = $userLevel->calculateDiscount((float) $totalAmount);
                }
            }

            // 计算最终支付金额（段位折扣在优惠券之后应用）
            $finalAmount = max(0, $totalAmount - $depositDiscount - $pointsDiscount - $couponDiscount - $levelDiscount);

            // 生成订单号
            $orderNo = 'ORD' . date('YmdHis') . str_pad((string) mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);

            // 创建订单
            $order = Order::create([
                'order_no' => $orderNo,
                'user_id' => $user->id,
                'table_id' => $tableId,
                'reservation_id' => $reservationId,
                'total_amount' => $totalAmount,
                'deposit_discount' => $depositDiscount,
                'points_discount' => $pointsDiscount,
                'points_used' => $usePoints ? $pointsUsed : 0,
                'user_coupon_id' => $userCouponId,
                'coupon_discount' => $couponDiscount,
                'user_level_code' => $userLevelCode,
                'level_discount' => $levelDiscount,
                'final_amount' => $finalAmount,
                'status' => 'pending',
            ]);

            // 创建订单项
            foreach ($orderItems as $itemData) {
                if ($itemData['type'] === 'combo') {
                    // 创建套餐订单项
                    OrderItem::create([
                        'order_id' => $order->id,
                        'type' => 'combo',
                        'combo_id' => $itemData['combo']->id,
                        'dish_id' => null,
                        'quantity' => $itemData['quantity'],
                        'price' => $itemData['price'],
                        'subtotal' => $itemData['subtotal'],
                        'original_total' => $itemData['original_total'],
                        'savings' => $itemData['savings'],
                    ]);

                    // 更新套餐销量
                    $itemData['combo']->increment('sold_count', $itemData['quantity']);
                } else {
                    // 创建菜品订单项
                    OrderItem::create([
                        'order_id' => $order->id,
                        'type' => 'dish',
                        'dish_id' => $itemData['dish']->id,
                        'combo_id' => null,
                        'quantity' => $itemData['quantity'],
                        'price' => $itemData['price'],
                        'subtotal' => $itemData['subtotal'],
                        'original_total' => null,
                        'savings' => null,
                    ]);

                    // 更新菜品销量
                    $itemData['dish']->increment('sales_count', $itemData['quantity']);
                }
            }

            // 如果使用了优惠券，标记为已使用
            if ($userCoupon) {
                $userCoupon->update([
                    'status' => 'used',
                    'used_at' => now(),
                    'order_id' => $order->id,
                ]);
            }

            // 如果使用了积分，扣除积分
            if ($usePoints && $pointsUsed > 0) {
                $memberPoint = MemberPoint::lockForUpdate()->where('user_id', $user->id)->first();
                if ($memberPoint) {
                    $memberPoint->available_points -= $pointsUsed;
                    $memberPoint->save();

                    // 记录积分使用流水
                    \App\Models\PointTransaction::create([
                        'user_id' => $user->id,
                        'type' => 'use',
                        'points' => -$pointsUsed,
                        'balance_after' => $memberPoint->available_points,
                        'source_type' => 'order',
                        'source_id' => $order->id,
                        'description' => "订单 {$orderNo} 使用积分抵扣",
                    ]);
                }
            }

            // 如果使用了定金抵扣，更新预约状态
            if ($useDeposit && $reservation && $depositDiscount > 0) {
                $reservation->update([
                    'order_id' => $order->id,
                    'deposit_status' => 'refunded',
                    'deposit_refunded_at' => now(),
                ]);
            }

            DB::commit();

            // 加载关联数据
            $order->load(['items.dish', 'user', 'table']);

            return response()->json([
                'code' => 200,
                'message' => '订单创建成功',
                'data' => $order,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('创建订单失败', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'code' => 500,
                'message' => '创建订单失败：' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 支付订单
     */
    public function pay(Request $request, int $orderId)
    {
        $validator = Validator::make($request->all(), [
            'payment_method' => [
                'required',
                Rule::in(['wechat', 'mock']),
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'code' => 400,
                'message' => '参数验证失败',
                'errors' => $validator->errors(),
            ], 400);
        }

        $user = Auth::user();
        $paymentMethod = $request->input('payment_method');

        // 检查支付方式是否启用
        $wechatEnabled = Configuration::getValue('wechat_payment_enabled', false);
        $mockEnabled = Configuration::getValue('mock_payment_enabled', true);

        if ($paymentMethod === 'wechat' && !$wechatEnabled) {
            return response()->json([
                'code' => 400,
                'message' => '微信支付未启用',
            ], 400);
        }

        if ($paymentMethod === 'mock' && !$mockEnabled) {
            return response()->json([
                'code' => 400,
                'message' => '模拟支付未启用',
            ], 400);
        }

        $order = Order::where('id', $orderId)
            ->where('user_id', $user->id)
            ->with(['items.dish'])
            ->first();

        if (!$order) {
            return response()->json([
                'code' => 404,
                'message' => '订单不存在',
            ], 404);
        }

        if ($order->status !== 'pending') {
            return response()->json([
                'code' => 400,
                'message' => '订单状态不正确，无法支付',
            ], 400);
        }

        try {
            DB::beginTransaction();

            if ($paymentMethod === 'mock') {
                // 模拟支付：直接标记为已支付
                $transactionId = 'MOCK' . date('YmdHis') . str_pad((string) mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
                
                // 使用最终支付金额（已扣除定金和积分）
                $paymentAmount = $order->final_amount ?? $order->total_amount;
                
                // 先设置为 paid 状态（触发积分发放），然后立即设置为 pending_review（允许评价）
                // 这样确保积分正常发放，同时订单进入待评价状态
                $order->update([
                    'status' => 'paid', // 先设置为已支付状态，触发积分发放
                    'payment_method' => 'mock',
                    'payment_transaction_id' => $transactionId,
                    'payment_data' => [
                        'method' => 'mock',
                        'paid_at' => now()->toDateTimeString(),
                        'original_amount' => $order->total_amount,
                        'deposit_discount' => $order->deposit_discount ?? 0,
                        'points_discount' => $order->points_discount ?? 0,
                        'points_used' => $order->points_used ?? 0,
                        'final_amount' => $paymentAmount,
                    ],
                    'paid_at' => now(),
                ]);

                // 刷新订单以获取最新状态（确保Observer已处理）
                $order->refresh();

                // 立即设置为 pending_review 状态（支付后可以评价）
                $order->update([
                    'status' => 'pending_review',
                ]);

                DB::commit();

                Log::info('模拟支付成功', [
                    'order_id' => $order->id,
                    'order_no' => $order->order_no,
                    'transaction_id' => $transactionId,
                ]);

                $order->refresh();
                $order->load(['items.dish', 'user', 'table']);

                return response()->json([
                    'code' => 200,
                    'message' => '支付成功',
                    'data' => $order,
                ]);
            } elseif ($paymentMethod === 'wechat') {
                // 微信支付：返回支付参数（暂时返回模拟数据）
                // TODO: 集成真实的微信支付SDK
                $transactionId = 'WX' . date('YmdHis') . str_pad((string) mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
                
                // 这里应该调用微信支付API获取支付参数
                // 暂时返回模拟数据
                $paymentData = [
                    'appId' => Configuration::getValue('wechat_app_id', ''),
                    'timeStamp' => (string) time(),
                    'nonceStr' => Str::random(32),
                    'package' => "prepay_id={$transactionId}",
                    'signType' => 'RSA',
                    'paySign' => Str::random(64), // 实际应该是签名
                ];

                // 保存支付信息（但不标记为已支付，等待微信回调）
                $order->update([
                    'payment_method' => 'wechat',
                    'payment_transaction_id' => $transactionId,
                    'payment_data' => $paymentData,
                ]);

                DB::commit();

                return response()->json([
                    'code' => 200,
                    'message' => '获取支付参数成功',
                    'data' => [
                        'order' => $order,
                        'payment_params' => $paymentData,
                    ],
                ]);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('支付订单失败', [
                'order_id' => $orderId,
                'payment_method' => $paymentMethod,
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
     * 获取订单列表
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $status = $request->input('status');
        $page = $request->input('page', 1);
        $pageSize = $request->input('page_size', 20);

        $query = Order::where('user_id', $user->id)
            ->with(['items.dish', 'table', 'user'])
            ->orderBy('created_at', 'desc');

        if ($status) {
            $query->where('status', $status);
        }

        $orders = $query->paginate($pageSize, ['*'], 'page', $page);

        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => [
                'data' => $orders->items(),
                'current_page' => $orders->currentPage(),
                'total_pages' => $orders->lastPage(),
                'total_count' => $orders->total(),
                'page_size' => $orders->perPage(),
            ],
        ]);
    }

    /**
     * 获取订单详情
     */
    public function show(int $orderId)
    {
        $user = Auth::user();

        $order = Order::where('id', $orderId)
            ->where('user_id', $user->id)
            ->with(['items.dish', 'user', 'table', 'reservation'])
            ->first();

        if (!$order) {
            return response()->json([
                'code' => 404,
                'message' => '订单不存在',
            ], 404);
        }

        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => $order,
        ]);
    }

    /**
     * 更新订单（应用抵扣选项）
     */
    public function update(Request $request, int $orderId)
    {
        $validator = Validator::make($request->all(), [
            'reservation_id' => 'nullable|integer|exists:reservations,id',
            'use_deposit' => 'nullable|boolean',
            'use_points' => 'nullable|boolean',
            'points_used' => 'nullable|integer|min:0',
            'user_coupon_id' => 'nullable|integer|exists:user_coupons,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'code' => 400,
                'message' => '参数验证失败',
                'errors' => $validator->errors(),
            ], 400);
        }

        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'code' => 401,
                'message' => '未登录',
            ], 401);
        }

        $order = Order::where('id', $orderId)
            ->where('user_id', $user->id)
            ->first();

        if (!$order) {
            return response()->json([
                'code' => 404,
                'message' => '订单不存在',
            ], 404);
        }

        if ($order->status !== 'pending') {
            return response()->json([
                'code' => 400,
                'message' => '只有待支付订单才能更新抵扣选项',
            ], 400);
        }

        try {
            DB::beginTransaction();

            $reservationId = $request->input('reservation_id', $order->reservation_id);
            $useDeposit = $request->boolean('use_deposit', false);
            $usePoints = $request->boolean('use_points', false);
            $pointsUsed = (int) $request->input('points_used', 0);
            $userCouponId = $request->input('user_coupon_id');

            // 验证预约（如果提供了预约ID）
            $reservation = null;
            $depositDiscount = 0;
            if ($reservationId) {
                $reservation = Reservation::where('id', $reservationId)
                    ->where('user_id', $user->id)
                    ->first();

                if (!$reservation) {
                    DB::rollBack();
                    return response()->json([
                        'code' => 404,
                        'message' => '预约不存在',
                    ], 404);
                }

                // 检查预约是否可以用于抵扣（已确认且已到达）
                if ($useDeposit && $reservation->status === 'confirmed' && $reservation->arrived_at && $reservation->deposit_status === 'paid') {
                    $depositDiscount = $reservation->deposit_amount ?? 0;
                }
            }

            // 验证积分抵扣
            $pointsDiscount = 0;
            if ($usePoints && $pointsUsed > 0) {
                /** @var \App\Models\User $user */
                $memberPoint = $this->pointService->getPoints($user);
                if ($memberPoint->available_points < $pointsUsed) {
                    DB::rollBack();
                    return response()->json([
                        'code' => 400,
                        'message' => '积分不足',
                    ], 400);
                }

                // 获取积分抵扣比例（多少积分=1元）
                $pointsToMoneyRate = (int) Configuration::getValue('points_to_money_rate', 100);
                $pointsDiscount = min($pointsUsed / $pointsToMoneyRate, $memberPoint->available_points / $pointsToMoneyRate);
            }

            // 验证优惠券
            $couponDiscount = 0;
            $userCoupon = null;
            if ($userCouponId) {
                // 首先检查优惠券是否存在
                $userCoupon = \App\Models\UserCoupon::where('id', $userCouponId)
                    ->where('user_id', $user->id)
                    ->with(['coupon.dish'])
                    ->first();

                if (!$userCoupon) {
                    DB::rollBack();
                    return response()->json([
                        'code' => 400,
                        'message' => '优惠券不存在',
                    ], 400);
                }

                // 如果优惠券已经被当前订单使用，允许继续使用
                $isUsedByCurrentOrder = $order->id && 
                                       $userCoupon->status === 'used' && 
                                       $userCoupon->order_id === $order->id;

                // 如果优惠券不是被当前订单使用，需要验证是否可用
                if (!$isUsedByCurrentOrder) {
                    if ($userCoupon->status !== 'unused') {
                        DB::rollBack();
                        return response()->json([
                            'code' => 400,
                            'message' => '优惠券已使用',
                        ], 400);
                    }

                    $coupon = $userCoupon->coupon;
                    if (!$coupon || !$coupon->isUsable()) {
                        DB::rollBack();
                        return response()->json([
                            'code' => 400,
                            'message' => '优惠券不可用',
                        ], 400);
                    }
                } else {
                    // 如果已经被当前订单使用，加载优惠券信息用于计算折扣
                    $coupon = $userCoupon->coupon;
                    if (!$coupon) {
                        DB::rollBack();
                        return response()->json([
                            'code' => 400,
                            'message' => '优惠券信息不存在',
                        ], 400);
                    }
                }

                // 确保 total_amount 转换为 float 类型
                $orderAmount = (float) $order->total_amount;
                $couponDiscount = $coupon->calculateDiscount($orderAmount);
                
                // 如果优惠券是兑换菜品类型，需要检查订单中是否包含该菜品
                if ($coupon->type === 'dish_exchange' && $coupon->dish_id) {
                    // 检查订单项中是否包含该菜品（包括套餐中的菜品）
                    $hasDish = $order->items()
                        ->where(function ($query) use ($coupon) {
                            $query->where('dish_id', $coupon->dish_id)
                                ->orWhereHas('combo.dishes', function ($q) use ($coupon) {
                                    $q->where('dish_id', $coupon->dish_id);
                                });
                        })
                        ->exists();
                    
                    if (!$hasDish) {
                        DB::rollBack();
                        return response()->json([
                            'code' => 400,
                            'message' => '订单中不包含该优惠券可兑换的菜品',
                        ], 400);
                    }
                }
            }

            // 计算段位折扣
            $levelDiscount = 0;
            $userLevelCode = $order->user_level_code;
            $memberPoint = $this->pointService->getPoints($user);
            if ($memberPoint && $memberPoint->level) {
                $userLevel = PointLevel::where('code', $memberPoint->level)
                    ->where('is_active', true)
                    ->first();
                
                if ($userLevel) {
                    $userLevelCode = $userLevel->code;
                    // 段位折扣基于订单总金额（在优惠券折扣之前）
                    $levelDiscount = $userLevel->calculateDiscount((float) $order->total_amount);
                }
            }

            // 计算最终支付金额（段位折扣在优惠券之后应用）
            $finalAmount = max(0, $order->total_amount - $depositDiscount - $pointsDiscount - $couponDiscount - $levelDiscount);

            // 如果之前使用了优惠券，需要恢复
            if ($order->user_coupon_id && $order->user_coupon_id != $userCouponId) {
                $oldUserCoupon = \App\Models\UserCoupon::find($order->user_coupon_id);
                if ($oldUserCoupon && $oldUserCoupon->status === 'used' && $oldUserCoupon->order_id === $order->id) {
                    $oldUserCoupon->update([
                        'status' => 'unused',
                        'used_at' => null,
                        'order_id' => null,
                    ]);
                }
            }

            // 如果使用了优惠券，且尚未被当前订单使用，标记为已使用
            if ($userCoupon && !($userCoupon->status === 'used' && $userCoupon->order_id === $order->id)) {
                $userCoupon->update([
                    'status' => 'used',
                    'used_at' => now(),
                    'order_id' => $order->id,
                ]);
            }

            // 更新订单
            $order->update([
                'reservation_id' => $reservationId,
                'deposit_discount' => $depositDiscount,
                'points_discount' => $pointsDiscount,
                'points_used' => $usePoints ? $pointsUsed : 0,
                'user_coupon_id' => $userCouponId,
                'coupon_discount' => $couponDiscount,
                'user_level_code' => $userLevelCode,
                'level_discount' => $levelDiscount,
                'final_amount' => $finalAmount,
            ]);

            DB::commit();

            // 加载关联数据
            $order->load(['items.dish', 'user', 'table', 'reservation']);

            return response()->json([
                'code' => 200,
                'message' => '订单更新成功',
                'data' => $order,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('更新订单失败', [
                'order_id' => $orderId,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'code' => 500,
                'message' => '更新订单失败：' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 取消订单
     */
    public function cancel(int $orderId)
    {
        $user = Auth::user();

        $order = Order::where('id', $orderId)
            ->where('user_id', $user->id)
            ->first();

        if (!$order) {
            return response()->json([
                'code' => 404,
                'message' => '订单不存在',
            ], 404);
        }

        if ($order->status === 'completed') {
            return response()->json([
                'code' => 400,
                'message' => '已完成订单无法取消',
            ], 400);
        }

        if ($order->status === 'cancelled') {
            return response()->json([
                'code' => 400,
                'message' => '订单已取消',
            ], 400);
        }

        if ($order->status === 'paid') {
            return response()->json([
                'code' => 400,
                'message' => '已支付订单无法取消，请联系客服',
            ], 400);
        }

        try {
            DB::beginTransaction();

            $order->update([
                'status' => 'cancelled',
            ]);

            DB::commit();

            Log::info('用户取消订单', [
                'order_id' => $order->id,
                'order_no' => $order->order_no,
                'user_id' => $user->id,
            ]);

            $order->refresh();
            $order->load(['items.dish', 'user', 'table']);

            return response()->json([
                'code' => 200,
                'message' => '订单已取消',
                'data' => $order,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('取消订单失败', [
                'order_id' => $orderId,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'code' => 500,
                'message' => '取消订单失败：' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 跳过评价，完成订单
     */
    public function skipReview(int $orderId)
    {
        $user = Auth::user();

        $order = Order::where('id', $orderId)
            ->where('user_id', $user->id)
            ->first();

        if (!$order) {
            return response()->json([
                'code' => 404,
                'message' => '订单不存在',
            ], 404);
        }

        if ($order->status !== 'pending_review') {
            return response()->json([
                'code' => 400,
                'message' => '订单状态不正确，无法跳过评价',
            ], 400);
        }

        try {
            DB::beginTransaction();

            $order->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            DB::commit();

            Log::info('用户跳过评价，订单已完成', [
                'order_id' => $order->id,
                'order_no' => $order->order_no,
                'user_id' => $user->id,
            ]);

            $order->refresh();
            $order->load(['items.dish', 'user', 'table']);

            return response()->json([
                'code' => 200,
                'message' => '订单已完成',
                'data' => $order,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('跳过评价失败', [
                'order_id' => $orderId,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'code' => 500,
                'message' => '操作失败：' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 获取可用的支付方式
     */
    public function getPaymentMethods()
    {
        $wechatEnabled = Configuration::getValue('wechat_payment_enabled', false);
        $mockEnabled = Configuration::getValue('mock_payment_enabled', true);
        $defaultMethod = Configuration::getValue('payment_method', 'mock');

        $methods = [];

        if ($mockEnabled) {
            $methods[] = [
                'code' => 'mock',
                'name' => '模拟支付',
                'description' => '用于测试的模拟支付方式',
                'is_default' => $defaultMethod === 'mock',
            ];
        }

        if ($wechatEnabled) {
            $methods[] = [
                'code' => 'wechat',
                'name' => '微信支付',
                'description' => '使用微信支付',
                'is_default' => $defaultMethod === 'wechat',
            ];
        }

        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => [
                'methods' => $methods,
                'default_method' => $defaultMethod,
            ],
        ]);
    }
}
