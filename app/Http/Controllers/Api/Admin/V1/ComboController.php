<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin\V1;

use App\Http\Controllers\Controller;
use App\Models\Combo;
use App\Models\ComboDish;
use App\Models\Dish;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ComboController extends Controller
{
    /**
     * 获取套餐列表
     */
    public function index(Request $request): JsonResponse
    {
        $query = Combo::with('dishes.dish');

        // 状态筛选
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // 搜索
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('name', 'like', "%{$search}%");
        }

        // 排序
        $query->orderBy('sort_order')->orderBy('created_at', 'desc');

        $perPage = $request->input('per_page', 15);
        $combos = $query->paginate($perPage);

        // 计算每个套餐的原价和优惠
        $combos->getCollection()->transform(function ($combo) {
            $combo->original_total = $combo->original_total;
            $combo->savings = $combo->savings;
            $combo->discount_percent = $combo->discount_percent;
            return $combo;
        });

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'combos' => $combos->items(),
                'pagination' => [
                    'total' => $combos->total(),
                    'per_page' => $combos->perPage(),
                    'current_page' => $combos->currentPage(),
                    'last_page' => $combos->lastPage(),
                ],
            ],
        ]);
    }

    /**
     * 获取套餐详情
     */
    public function show(int $id): JsonResponse
    {
        $combo = Combo::with('dishes.dish')->findOrFail($id);
        $combo->original_total = $combo->original_total;
        $combo->savings = $combo->savings;
        $combo->discount_percent = $combo->discount_percent;

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'combo' => $combo,
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
            'price' => 'required|numeric|min:0',
            'image_url' => 'nullable|url|max:255',
            'stock' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer',
            'dishes' => 'required|array|min:1',
            'dishes.*.dish_id' => 'required|integer|exists:dishes,id',
            'dishes.*.quantity' => 'required|integer|min:1',
        ]);

        try {
            DB::beginTransaction();

            $combo = Combo::create([
                'name' => $request->input('name'),
                'description' => $request->input('description'),
                'price' => $request->input('price'),
                'image_url' => $request->input('image_url'),
                'stock' => $request->input('stock', 0),
                'is_active' => $request->boolean('is_active', true),
                'sort_order' => $request->input('sort_order', 0),
            ]);

            // 创建套餐菜品关联
            foreach ($request->input('dishes') as $index => $dishData) {
                ComboDish::create([
                    'combo_id' => $combo->id,
                    'dish_id' => $dishData['dish_id'],
                    'quantity' => $dishData['quantity'],
                    'sort_order' => $index,
                ]);
            }

            DB::commit();

            $combo->load('dishes.dish');
            $combo->original_total = $combo->original_total;
            $combo->savings = $combo->savings;
            $combo->discount_percent = $combo->discount_percent;

            return response()->json([
                'code' => 201,
                'message' => '套餐创建成功',
                'data' => [
                    'combo' => $combo,
                ],
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'code' => 500,
                'message' => '创建套餐失败：' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 更新套餐
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $combo = Combo::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|string|max:100',
            'description' => 'nullable|string',
            'price' => 'sometimes|numeric|min:0',
            'image_url' => 'nullable|url|max:255',
            'stock' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer',
            'dishes' => 'sometimes|array|min:1',
            'dishes.*.dish_id' => 'required|integer|exists:dishes,id',
            'dishes.*.quantity' => 'required|integer|min:1',
        ]);

        try {
            DB::beginTransaction();

            $combo->update($request->only([
                'name',
                'description',
                'price',
                'image_url',
                'stock',
                'is_active',
                'sort_order',
            ]));

            // 如果提供了菜品列表，更新套餐菜品关联
            if ($request->has('dishes')) {
                // 删除旧的关联
                ComboDish::where('combo_id', $combo->id)->delete();

                // 创建新的关联
                foreach ($request->input('dishes') as $index => $dishData) {
                    ComboDish::create([
                        'combo_id' => $combo->id,
                        'dish_id' => $dishData['dish_id'],
                        'quantity' => $dishData['quantity'],
                        'sort_order' => $index,
                    ]);
                }
            }

            DB::commit();

            $combo->load('dishes.dish');
            $combo->original_total = $combo->original_total;
            $combo->savings = $combo->savings;
            $combo->discount_percent = $combo->discount_percent;

            return response()->json([
                'code' => 200,
                'message' => '套餐更新成功',
                'data' => [
                    'combo' => $combo,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'code' => 500,
                'message' => '更新套餐失败：' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 删除套餐
     */
    public function destroy(int $id): JsonResponse
    {
        $combo = Combo::findOrFail($id);

        // 检查是否有订单使用此套餐
        if ($combo->orderItems()->exists()) {
            return response()->json([
                'code' => 400,
                'message' => '该套餐已被订单使用，无法删除',
            ], 400);
        }

        $combo->delete();

        return response()->json([
            'code' => 200,
            'message' => '套餐删除成功',
        ]);
    }
}

