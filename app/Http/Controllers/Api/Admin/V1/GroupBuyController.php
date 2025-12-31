<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin\V1;

use App\Http\Controllers\Controller;
use App\Models\GroupBuy;
use App\Models\GroupBuyItem;
use App\Models\GroupBuyOrder;
use App\Models\Dish;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GroupBuyController extends Controller
{
    /**
     * 获取团购列表
     */
    public function index(Request $request): JsonResponse
    {
        $query = GroupBuy::with(['items.dish']);

        // 搜索
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // 状态筛选
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        // 启用状态筛选
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // 排序
        $sortBy = $request->input('sort_by', 'sort_order');
        $sortOrder = $request->input('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        $perPage = $request->input('per_page', 15);
        $groupBuys = $query->paginate($perPage);

        // 统计每个套餐的订单数
        foreach ($groupBuys->items() as $groupBuy) {
            $groupBuy->orders_count = GroupBuyOrder::where('group_buy_id', $groupBuy->id)->count();
            $groupBuy->paid_orders_count = GroupBuyOrder::where('group_buy_id', $groupBuy->id)->where('status', 'paid')->count();
            $groupBuy->used_orders_count = GroupBuyOrder::where('group_buy_id', $groupBuy->id)->where('status', 'used')->count();
        }

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
     * 获取套餐详情
     */
    public function show(int $id): JsonResponse
    {
        $groupBuy = GroupBuy::with(['items.dish', 'orders.user'])->findOrFail($id);
        
        $groupBuy->orders_count = GroupBuyOrder::where('group_buy_id', $id)->count();
        $groupBuy->paid_orders_count = GroupBuyOrder::where('group_buy_id', $id)->where('status', 'paid')->count();
        $groupBuy->used_orders_count = GroupBuyOrder::where('group_buy_id', $id)->where('status', 'used')->count();

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'group_buy' => $groupBuy,
            ],
        ]);
    }

    /**
     * 创建套餐
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'image_url' => 'nullable|url|max:255',
            'original_price' => 'required|numeric|min:0',
            'group_price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'start_time' => 'nullable|date',
            'end_time' => 'nullable|date|after_or_equal:start_time',
            'valid_from' => 'nullable|date',
            'valid_to' => 'nullable|date|after_or_equal:valid_from',
            'valid_days' => 'nullable|integer|min:0',
            'limit_per_user' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer',
            'status' => 'required|in:draft,published,ongoing,ended,cancelled',
            'rules' => 'nullable|array',
            'items' => 'required|array|min:1',
            'items.*.dish_id' => 'required|exists:dishes,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.sort_order' => 'nullable|integer',
        ]);

        try {
            DB::beginTransaction();

            $groupBuy = GroupBuy::create($request->only([
                'name', 'description', 'image_url', 'original_price', 'group_price',
                'stock', 'start_time', 'end_time',
                'valid_from', 'valid_to', 'valid_days', 'limit_per_user',
                'is_active', 'sort_order', 'status', 'rules',
            ]));

            // 创建套餐菜品
            foreach ($request->input('items', []) as $item) {
                GroupBuyItem::create([
                    'group_buy_id' => $groupBuy->id,
                    'dish_id' => $item['dish_id'],
                    'quantity' => $item['quantity'],
                    'sort_order' => $item['sort_order'] ?? 0,
                ]);
            }

            DB::commit();

            $groupBuy->load(['items.dish']);

            Log::info('团购创建成功', [
                'group_buy_id' => $groupBuy->id,
                'name' => $groupBuy->name,
            ]);

            return response()->json([
                'code' => 201,
                'message' => '团购创建成功',
                'data' => [
                    'group_buy' => $groupBuy,
                ],
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('套餐创建失败', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'code' => 500,
                'message' => '套餐创建失败：' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 更新套餐
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $groupBuy = GroupBuy::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|string|max:100',
            'description' => 'nullable|string',
            'image_url' => 'nullable|url|max:255',
            'original_price' => 'sometimes|numeric|min:0',
            'group_price' => 'sometimes|numeric|min:0',
            'stock' => 'sometimes|integer|min:0',
            'start_time' => 'nullable|date',
            'end_time' => 'nullable|date|after_or_equal:start_time',
            'valid_from' => 'nullable|date',
            'valid_to' => 'nullable|date|after_or_equal:valid_from',
            'valid_days' => 'nullable|integer|min:0',
            'limit_per_user' => 'nullable|integer|min:0',
            'is_active' => 'sometimes|boolean',
            'sort_order' => 'nullable|integer',
            'status' => 'sometimes|in:draft,published,ongoing,ended,cancelled',
            'rules' => 'nullable|array',
            'items' => 'sometimes|array|min:1',
            'items.*.dish_id' => 'required_with:items|exists:dishes,id',
            'items.*.quantity' => 'required_with:items|integer|min:1',
            'items.*.sort_order' => 'nullable|integer',
        ]);

        try {
            DB::beginTransaction();

            $groupBuy->update($request->only([
                'name', 'description', 'image_url', 'original_price', 'group_price',
                'stock', 'start_time', 'end_time',
                'valid_from', 'valid_to', 'valid_days', 'limit_per_user',
                'is_active', 'sort_order', 'status', 'rules',
            ]));

            // 如果提供了items，更新菜品列表
            if ($request->has('items')) {
                // 删除旧的菜品
                GroupBuyItem::where('group_buy_id', $groupBuy->id)->delete();
                
                // 创建新的菜品
                foreach ($request->input('items', []) as $item) {
                    GroupBuyItem::create([
                        'group_buy_id' => $groupBuy->id,
                        'dish_id' => $item['dish_id'],
                        'quantity' => $item['quantity'],
                        'sort_order' => $item['sort_order'] ?? 0,
                    ]);
                }
            }

            DB::commit();

            $groupBuy->load(['items.dish']);

            Log::info('套餐更新成功', [
                'group_buy_id' => $groupBuy->id,
                'name' => $groupBuy->name,
            ]);

            return response()->json([
                'code' => 200,
                'message' => '套餐更新成功',
                'data' => [
                    'group_buy' => $groupBuy,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('团购更新失败', [
                'group_buy_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'code' => 500,
                'message' => '团购更新失败：' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 删除套餐
     */
    public function destroy(int $id): JsonResponse
    {
        $groupBuy = GroupBuy::findOrFail($id);

        // 检查是否有已支付的订单
        $paidOrdersCount = GroupBuyOrder::where('group_buy_id', $id)
            ->whereIn('status', ['paid', 'used'])
            ->count();

        if ($paidOrdersCount > 0) {
            return response()->json([
                'code' => 400,
                'message' => '该套餐已有已支付或已使用的订单，无法删除',
            ], 400);
        }

        try {
            DB::beginTransaction();

            // 删除待支付的订单
            GroupBuyOrder::where('group_buy_id', $id)->delete();
            
            // 删除菜品关联
            GroupBuyItem::where('group_buy_id', $id)->delete();
            
            // 删除套餐
            $groupBuy->delete();

            DB::commit();

            Log::info('套餐删除成功', [
                'group_buy_id' => $id,
            ]);

            return response()->json([
                'code' => 200,
                'message' => '套餐删除成功',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('套餐删除失败', [
                'group_buy_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'code' => 500,
                'message' => '套餐删除失败：' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 更新套餐状态
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:draft,published,ongoing,ended,cancelled',
        ]);

        $groupBuy = GroupBuy::findOrFail($id);
        $oldStatus = $groupBuy->status;
        $newStatus = $request->input('status');

        $groupBuy->update(['status' => $newStatus]);

        Log::info('套餐状态更新', [
            'group_buy_id' => $id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
        ]);

        return response()->json([
            'code' => 200,
            'message' => '套餐状态更新成功',
            'data' => [
                'group_buy' => $groupBuy->fresh(),
            ],
        ]);
    }

    /**
     * 获取套餐订单列表
     */
    public function orders(Request $request, int $id): JsonResponse
    {
        $query = GroupBuyOrder::with(['user', 'order'])
            ->where('group_buy_id', $id);

        // 状态筛选
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        $query->orderBy('created_at', 'desc');

        $perPage = $request->input('per_page', 15);
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
}
