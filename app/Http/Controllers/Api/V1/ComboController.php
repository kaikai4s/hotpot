<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Combo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ComboController extends Controller
{
    /**
     * 获取套餐列表（前端用户）
     */
    public function index(Request $request): JsonResponse
    {
        $query = Combo::with('dishes.dish')
            ->where('is_active', true); // 只显示启用的套餐

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
            case 'savings_desc':
                // 按优惠金额降序（需要计算）
                $query->get()->sortByDesc(function ($combo) {
                    return $combo->savings;
                });
                break;
            default:
                $query->orderBy('sort_order')->orderBy('created_at', 'desc');
        }

        $perPage = $request->input('per_page', 20);
        $combos = $query->paginate($perPage);

        // 计算每个套餐的原价和优惠
        $combos->getCollection()->transform(function ($combo) {
            // 只返回可用的套餐
            if (!$combo->isAvailable()) {
                return null;
            }
            $combo->original_total = $combo->original_total;
            $combo->savings = $combo->savings;
            $combo->discount_percent = $combo->discount_percent;
            return $combo;
        })->filter();

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
        $combo = Combo::with('dishes.dish')->where('is_active', true)->findOrFail($id);

        if (!$combo->isAvailable()) {
            return response()->json([
                'code' => 400,
                'message' => '该套餐暂不可用',
            ], 400);
        }

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
}

