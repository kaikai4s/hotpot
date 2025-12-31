<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin\V1;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\UserCoupon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CouponController extends Controller
{
    /**
     * 获取优惠券列表
     */
    public function index(Request $request): JsonResponse
    {
        $query = Coupon::query();

        // 搜索
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('name', 'like', "%{$search}%");
        }

        // 类型筛选
        if ($request->has('type')) {
            $query->where('type', $request->input('type'));
        }

        // 状态筛选
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // 排序
        $query->orderBy('created_at', 'desc');

        $perPage = $request->input('per_page', 15);
        $coupons = $query->paginate($perPage);

        // 加载关联并统计使用情况
        $coupons->load('dish');
        foreach ($coupons->items() as $coupon) {
            $coupon->used_count = UserCoupon::where('coupon_id', $coupon->id)
                ->where('status', 'used')
                ->count();
            $coupon->unused_count = UserCoupon::where('coupon_id', $coupon->id)
                ->where('status', 'unused')
                ->count();
        }

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'coupons' => $coupons->items(),
                'pagination' => [
                    'total' => $coupons->total(),
                    'per_page' => $coupons->perPage(),
                    'current_page' => $coupons->currentPage(),
                    'last_page' => $coupons->lastPage(),
                ],
            ],
        ]);
    }

    /**
     * 获取优惠券详情
     */
    public function show(int $id): JsonResponse
    {
        $coupon = Coupon::findOrFail($id);
        $coupon->used_count = UserCoupon::where('coupon_id', $id)->where('status', 'used')->count();
        $coupon->unused_count = UserCoupon::where('coupon_id', $id)->where('status', 'unused')->count();

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'coupon' => $coupon,
            ],
        ]);
    }

    /**
     * 创建优惠券
     */
    public function store(Request $request): JsonResponse
    {
        $rules = [
            'name' => 'required|string|max:100',
            'type' => 'required|in:discount,cash,points,dish_exchange,fixed_amount,percentage,new_user',
            'value' => 'required|numeric|min:0',
            'min_amount' => 'nullable|numeric|min:0',
            'points_required' => 'required|integer|min:0',
            'stock' => 'required|integer|min:0',
            'valid_from' => 'nullable|date',
            'valid_to' => 'nullable|date|after_or_equal:valid_from',
            'is_active' => 'boolean',
            'is_new_user_only' => 'boolean',
            'description' => 'nullable|string|max:500',
            'usage_instructions' => 'nullable|string|max:1000',
            'image_url' => 'nullable|url|max:255',
        ];

        // 如果是兑换菜品类型，需要菜品ID
        if ($request->input('type') === 'dish_exchange') {
            $rules['dish_id'] = 'required|exists:dishes,id';
        } else {
            $rules['dish_id'] = 'nullable|exists:dishes,id';
        }

        // 如果是百分比类型，value应该在0-100之间
        if ($request->input('type') === 'percentage') {
            $rules['value'] = 'required|numeric|min:0|max:100';
        }

        $request->validate($rules);

        $coupon = Coupon::create($request->all());

        // 加载关联
        $coupon->load('dish');

        return response()->json([
            'code' => 201,
            'message' => '优惠券创建成功',
            'data' => [
                'coupon' => $coupon,
            ],
        ], 201);
    }

    /**
     * 更新优惠券
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $coupon = Coupon::findOrFail($id);

        $rules = [
            'name' => 'sometimes|string|max:100',
            'type' => 'sometimes|in:discount,cash,points,dish_exchange,fixed_amount,percentage,new_user',
            'value' => 'sometimes|numeric|min:0',
            'min_amount' => 'nullable|numeric|min:0',
            'dish_id' => 'nullable|exists:dishes,id',
            'points_required' => 'sometimes|integer|min:0',
            'stock' => 'sometimes|integer|min:0',
            'valid_from' => 'nullable|date',
            'valid_to' => 'nullable|date|after_or_equal:valid_from',
            'is_active' => 'sometimes|boolean',
            'is_new_user_only' => 'sometimes|boolean',
            'description' => 'nullable|string|max:500',
            'usage_instructions' => 'nullable|string|max:1000',
            'image_url' => 'nullable|url|max:255',
        ];

        // 如果是兑换菜品类型，需要菜品ID
        if ($request->has('type') && $request->input('type') === 'dish_exchange') {
            $rules['dish_id'] = 'required|exists:dishes,id';
        }

        // 如果是百分比类型，value应该在0-100之间
        if ($request->has('type') && $request->input('type') === 'percentage') {
            $rules['value'] = 'sometimes|numeric|min:0|max:100';
        }

        $request->validate($rules);

        $coupon->update($request->all());
        $coupon->load('dish');

        return response()->json([
            'code' => 200,
            'message' => '优惠券更新成功',
            'data' => [
                'coupon' => $coupon,
            ],
        ]);
    }

    /**
     * 删除优惠券
     */
    public function destroy(int $id): JsonResponse
    {
        $coupon = Coupon::findOrFail($id);

        // 检查是否有用户已领取
        $usedCount = UserCoupon::where('coupon_id', $id)->where('status', 'used')->count();
        if ($usedCount > 0) {
            return response()->json([
                'code' => 400,
                'message' => '该优惠券已被使用，无法删除',
            ], 400);
        }

        // 删除未使用的用户优惠券
        UserCoupon::where('coupon_id', $id)->delete();

        $coupon->delete();

        return response()->json([
            'code' => 200,
            'message' => '优惠券删除成功',
        ]);
    }

    /**
     * 获取优惠券使用记录
     */
    public function usage(Request $request, int $id): JsonResponse
    {
        $query = UserCoupon::with(['user', 'coupon'])
            ->where('coupon_id', $id);

        // 状态筛选
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        $query->orderBy('created_at', 'desc');

        $perPage = $request->input('per_page', 15);
        $usage = $query->paginate($perPage);

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'usage' => $usage->items(),
                'pagination' => [
                    'total' => $usage->total(),
                    'per_page' => $usage->perPage(),
                    'current_page' => $usage->currentPage(),
                    'last_page' => $usage->lastPage(),
                ],
            ],
        ]);
    }
}

