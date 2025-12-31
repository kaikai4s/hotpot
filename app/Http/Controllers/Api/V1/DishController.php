<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Dish;
use App\Models\DishCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DishController extends Controller
{
    /**
     * 获取菜品列表（前端用户）
     */
    public function index(Request $request): JsonResponse
    {
        $query = Dish::with('category')
            ->where('status', 'available'); // 只显示在售的菜品

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
        $sortBy = $request->input('sort', 'default');
        switch ($sortBy) {
            case 'price_asc':
                $query->orderBy('price', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('price', 'desc');
                break;
            case 'rating_desc':
                $query->orderBy('average_rating', 'desc');
                break;
            case 'sales_desc':
                $query->orderBy('sales_count', 'desc');
                break;
            default:
                $query->orderBy('sort_order')->orderBy('created_at', 'desc');
        }

        $perPage = $request->input('per_page', 20);
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
     * 获取菜品分类列表
     */
    public function categories(): JsonResponse
    {
        $categories = DishCategory::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'categories' => $categories,
            ],
        ]);
    }

    /**
     * 获取菜品详情
     */
    public function show(int $id): JsonResponse
    {
        $dish = Dish::with('category')
            ->where('status', 'available')
            ->findOrFail($id);

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'dish' => $dish,
            ],
        ]);
    }
}

