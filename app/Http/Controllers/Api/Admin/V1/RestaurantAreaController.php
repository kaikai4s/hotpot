<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin\V1;

use App\Http\Controllers\Controller;
use App\Models\RestaurantArea;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RestaurantAreaController extends Controller
{
    /**
     * 获取所有区域列表
     */
    public function index(): JsonResponse
    {
        $areas = RestaurantArea::orderBy('sort_order')->orderBy('id')->get();

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'areas' => $areas,
            ],
        ]);
    }

    /**
     * 创建区域
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:50',
            'type' => 'required|string|max:20',
            'boundaries' => 'required|array',
            'color' => 'nullable|string|max:20',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $area = RestaurantArea::create([
            'name' => $request->input('name'),
            'type' => $request->input('type'),
            'boundaries' => $request->input('boundaries'),
            'color' => $request->input('color'),
            'sort_order' => $request->input('sort_order', 0),
            'is_active' => $request->input('is_active', true),
        ]);

        return response()->json([
            'code' => 201,
            'message' => '区域创建成功',
            'data' => [
                'area' => $area,
            ],
        ], 201);
    }

    /**
     * 更新区域
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'name' => 'sometimes|string|max:50',
            'type' => 'sometimes|string|max:20',
            'boundaries' => 'sometimes|array',
            'color' => 'nullable|string|max:20',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $area = RestaurantArea::findOrFail($id);
        $area->update($request->only([
            'name',
            'type',
            'boundaries',
            'color',
            'sort_order',
            'is_active',
        ]));

        return response()->json([
            'code' => 200,
            'message' => '区域更新成功',
            'data' => [
                'area' => $area,
            ],
        ]);
    }

    /**
     * 批量更新区域
     */
    public function updateBatch(Request $request): JsonResponse
    {
        $request->validate([
            'areas' => 'required|array',
            'areas.*.id' => 'required|integer|exists:restaurant_areas,id',
            'areas.*.name' => 'sometimes|string|max:50',
            'areas.*.type' => 'sometimes|string|max:20',
            'areas.*.boundaries' => 'sometimes|array',
            'areas.*.color' => 'nullable|string|max:20',
            'areas.*.sort_order' => 'nullable|integer|min:0',
            'areas.*.is_active' => 'nullable|boolean',
        ]);

        foreach ($request->input('areas') as $areaData) {
            RestaurantArea::where('id', $areaData['id'])->update(
                array_filter($areaData, fn($key) => $key !== 'id', ARRAY_FILTER_USE_KEY)
            );
        }

        return response()->json([
            'code' => 200,
            'message' => '区域批量更新成功',
        ]);
    }

    /**
     * 删除区域
     */
    public function destroy(int $id): JsonResponse
    {
        $area = RestaurantArea::findOrFail($id);
        $area->delete();

        return response()->json([
            'code' => 200,
            'message' => '区域删除成功',
        ]);
    }
}

