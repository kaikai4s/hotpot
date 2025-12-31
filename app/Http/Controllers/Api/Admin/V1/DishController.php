<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin\V1;

use App\Http\Controllers\Controller;
use App\Models\Dish;
use App\Models\DishCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DishController extends Controller
{
    /**
     * 获取菜品列表
     */
    public function index(Request $request): JsonResponse
    {
        $query = Dish::with('category');

        // 状态筛选
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        // 分类筛选
        if ($request->has('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        // 搜索
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('name', 'like', "%{$search}%");
        }

        // 排序
        $query->orderBy('sort_order')->orderBy('created_at', 'desc');

        $perPage = $request->input('per_page', 15);
        $dishes = $query->paginate($perPage);

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'dishes' => $dishes->items(),
                'pagination' => [
                    'total' => $dishes->total(),
                    'per_page' => $dishes->perPage(),
                    'current_page' => $dishes->currentPage(),
                    'last_page' => $dishes->lastPage(),
                ],
            ],
        ]);
    }

    /**
     * 获取菜品详情
     */
    public function show(int $id): JsonResponse
    {
        $dish = Dish::with('category')->findOrFail($id);

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'dish' => $dish,
            ],
        ]);
    }

    /**
     * 创建菜品
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'image_url' => 'nullable|url|max:255',
            'category_id' => 'required|integer|exists:dish_categories,id',
            'status' => 'required|in:available,sold_out,disabled',
            'sort_order' => 'nullable|integer',
        ]);

        $dish = Dish::create($request->all());

        return response()->json([
            'code' => 201,
            'message' => '菜品创建成功',
            'data' => [
                'dish' => $dish->load('category'),
            ],
        ], 201);
    }

    /**
     * 更新菜品
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $dish = Dish::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|string|max:100',
            'description' => 'nullable|string',
            'price' => 'sometimes|numeric|min:0',
            'image_url' => 'nullable|url|max:255',
            'category_id' => 'sometimes|integer|exists:dish_categories,id',
            'status' => 'sometimes|in:available,sold_out,disabled',
            'sort_order' => 'nullable|integer',
        ]);

        $dish->update($request->all());

        return response()->json([
            'code' => 200,
            'message' => '菜品更新成功',
            'data' => [
                'dish' => $dish->load('category'),
            ],
        ]);
    }

    /**
     * 删除菜品
     */
    public function destroy(int $id): JsonResponse
    {
        $dish = Dish::findOrFail($id);
        $dish->delete();

        return response()->json([
            'code' => 200,
            'message' => '菜品删除成功',
        ]);
    }

    /**
     * 获取菜品分类列表
     */
    public function categories(): JsonResponse
    {
        $categories = DishCategory::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'categories' => $categories,
            ],
        ]);
    }
}

