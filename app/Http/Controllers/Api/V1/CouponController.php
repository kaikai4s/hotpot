<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\UserCoupon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CouponController extends Controller
{
    /**
     * 获取可兑换的优惠券列表（前端用户）- 仅返回需要积分兑换的
     */
    public function getAvailableCoupons(Request $request): JsonResponse
    {
        $query = Coupon::where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('valid_from')
                    ->orWhere('valid_from', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('valid_to')
                    ->orWhere('valid_to', '>=', now());
            })
            ->where('stock', '>', 0)
            ->where('points_required', '>', 0)
            ->orderBy('points_required', 'asc');

        $coupons = $query->get();

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'coupons' => $coupons,
            ],
        ]);
    }

    /**
     * 获取所有可领取的优惠券列表（前端用户）- 包括新用户专享和可直接领取的
     */
    public function getAllAvailableCoupons(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json([
                'code' => 401,
                'message' => '请先登录',
            ], 401);
        }

        // 检查用户是否为新用户（没有订单）
        $isNewUser = \App\Models\Order::where('user_id', $user->id)
            ->whereIn('status', ['paid', 'completed', 'pending_review'])
            ->doesntExist();

        $query = Coupon::where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('valid_from')
                    ->orWhere('valid_from', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('valid_to')
                    ->orWhere('valid_to', '>=', now());
            })
            ->where('stock', '>', 0)
            ->where(function ($q) use ($isNewUser) {
                // 如果用户不是新用户，排除新用户专享优惠券
                if (!$isNewUser) {
                    $q->where('is_new_user_only', false);
                }
            })
            ->with('dish')
            ->orderBy('is_new_user_only', 'desc')
            ->orderBy('points_required', 'asc')
            ->orderBy('created_at', 'desc');

        $coupons = $query->get();

        // 检查用户是否已领取过每个优惠券
        $userCouponIds = UserCoupon::where('user_id', $user->id)
            ->whereIn('coupon_id', $coupons->pluck('id'))
            ->pluck('coupon_id')
            ->toArray();

        // 为每个优惠券添加状态信息
        $couponsWithStatus = $coupons->map(function ($coupon) use ($userCouponIds, $user) {
            $hasClaimed = in_array($coupon->id, $userCouponIds);
            
            // 确定分类
            $category = 'discount';
            if ($coupon->is_new_user_only) {
                $category = 'new_user';
            } elseif ($coupon->points_required > 0) {
                $category = 'points';
            }

            // 格式化折扣显示
            $discount = '¥' . number_format((float) $coupon->value, 2);
            if ($coupon->type === 'percentage') {
                $discount = $coupon->value . '%';
            } elseif ($coupon->type === 'new_user' || $coupon->type === 'fixed_amount') {
                $discount = '¥' . number_format((float) $coupon->value, 2);
            }

            return [
                'id' => $coupon->id,
                'name' => $coupon->name,
                'type' => $coupon->type,
                'category' => $category,
                'discount' => $discount,
                'description' => $coupon->description ?: ($coupon->is_new_user_only ? '新用户首次下单' : ''),
                'min_amount' => (float) $coupon->min_amount,
                'points_required' => $coupon->points_required,
                'expires_at' => $coupon->valid_to ? $coupon->valid_to->toISOString() : null,
                'status' => $hasClaimed ? 'claimed' : 'available',
                'is_new_user_only' => $coupon->is_new_user_only,
            ];
        });

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'coupons' => $couponsWithStatus,
            ],
        ]);
    }

    /**
     * 领取优惠券（前端用户）
     */
    public function claimCoupon(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json([
                'code' => 401,
                'message' => '请先登录',
            ], 401);
        }

        $request->validate([
            'coupon_id' => 'required|integer|exists:coupons,id',
        ]);

        $couponId = $request->input('coupon_id');
        $coupon = Coupon::findOrFail($couponId);

        // 检查优惠券是否可用
        if (!$coupon->isUsable()) {
            return response()->json([
                'code' => 400,
                'message' => '优惠券不可用',
            ], 400);
        }

        // 检查是否仅限新用户
        if ($coupon->is_new_user_only) {
            $isNewUser = \App\Models\Order::where('user_id', $user->id)
                ->whereIn('status', ['paid', 'completed', 'pending_review'])
                ->doesntExist();
            
            if (!$isNewUser) {
                return response()->json([
                    'code' => 400,
                    'message' => '此优惠券仅限新用户领取',
                ], 400);
            }
        }

        // 检查是否已领取
        $hasClaimed = UserCoupon::where('user_id', $user->id)
            ->where('coupon_id', $couponId)
            ->exists();

        if ($hasClaimed) {
            return response()->json([
                'code' => 400,
                'message' => '您已领取过此优惠券',
            ], 400);
        }

        // 如果需要积分，检查积分是否足够
        if ($coupon->points_required > 0) {
            $userPoints = \App\Models\MemberPoint::where('user_id', $user->id)->first();
            if (!$userPoints || $userPoints->available_points < $coupon->points_required) {
                return response()->json([
                    'code' => 400,
                    'message' => '积分不足',
                ], 400);
            }

            // 扣除积分
            $userPoints->available_points -= $coupon->points_required;
            $userPoints->save();

            // 记录积分流水
            \App\Models\PointTransaction::create([
                'user_id' => $user->id,
                'type' => 'redeem',
                'points' => -$coupon->points_required,
                'balance_after' => $userPoints->available_points,
                'source_type' => 'coupon',
                'source_id' => $coupon->id,
                'description' => "兑换优惠券：{$coupon->name}",
            ]);
        }

        // 创建用户优惠券
        $userCoupon = UserCoupon::create([
            'user_id' => $user->id,
            'coupon_id' => $coupon->id,
            'code' => 'CP' . strtoupper(uniqid()),
            'status' => 'unused',
            'expires_at' => $coupon->valid_to,
            'obtained_from' => $coupon->points_required > 0 ? 'points_redeem' : 'direct_claim',
            'obtained_at' => now(),
        ]);

        // 减少库存
        $coupon->decrement('stock');

        return response()->json([
            'code' => 200,
            'message' => $coupon->points_required > 0 ? '兑换成功' : '领取成功',
            'data' => [
                'user_coupon' => $userCoupon,
            ],
        ]);
    }

    /**
     * 获取用户已拥有的可用优惠券（用于订单结算）
     */
    public function getUserCoupons(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json([
                'code' => 401,
                'message' => '请先登录',
            ], 401);
        }

        $minAmount = $request->input('min_amount', 0);
        
        $query = UserCoupon::where('user_id', $user->id)
            ->where('status', 'unused')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>=', now());
            })
            ->with(['coupon.dish']);

        $userCoupons = $query->get();

        // 过滤可用优惠券（检查最低使用金额和可用性）
        $availableCoupons = $userCoupons->filter(function ($userCoupon) use ($minAmount) {
            $coupon = $userCoupon->coupon;
            if (!$coupon || !$coupon->isUsable()) {
                return false;
            }
            
            // 检查最低使用金额
            if ($coupon->min_amount > 0 && $minAmount < $coupon->min_amount) {
                return false;
            }
            
            return true;
        })->values();

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'coupons' => $availableCoupons,
            ],
        ]);
    }
}

