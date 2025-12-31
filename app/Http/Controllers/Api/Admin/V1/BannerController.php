<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin\V1;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BannerController extends Controller
{
    /**
     * 获取轮播图列表
     */
    public function index(Request $request): JsonResponse
    {
        $query = Banner::query();

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $banners = $query->ordered()->paginate($request->input('page_size', 10));

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'banners' => $banners->items(),
                'pagination' => [
                    'total' => $banners->total(),
                    'current_page' => $banners->currentPage(),
                    'last_page' => $banners->lastPage(),
                    'per_page' => $banners->perPage(),
                ],
            ],
        ]);
    }

    /**
     * 获取轮播图详情
     */
    public function show(int $id): JsonResponse
    {
        $banner = Banner::findOrFail($id);

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'banner' => $banner,
            ],
        ]);
    }

    /**
     * 创建轮播图
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image_url' => 'required|string|url|max:500',
            'link_url' => 'nullable|string|url|max:500',
            'link_type' => ['required', 'string', Rule::in(['none', 'internal', 'external'])],
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'start_at' => 'nullable|date',
            'end_at' => 'nullable|date|after:start_at',
        ]);

        $banner = Banner::create($request->only([
            'title',
            'description',
            'image_url',
            'link_url',
            'link_type',
            'sort_order',
            'is_active',
            'start_at',
            'end_at',
        ]));

        return response()->json([
            'code' => 200,
            'message' => '轮播图创建成功',
            'data' => [
                'banner' => $banner,
            ],
        ], 201);
    }

    /**
     * 更新轮播图
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $banner = Banner::findOrFail($id);

        $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'image_url' => 'sometimes|string|url|max:500',
            'link_url' => 'nullable|string|url|max:500',
            'link_type' => ['sometimes', 'string', Rule::in(['none', 'internal', 'external'])],
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'sometimes|boolean',
            'start_at' => 'nullable|date',
            'end_at' => 'nullable|date|after:start_at',
        ]);

        $banner->update($request->only([
            'title',
            'description',
            'image_url',
            'link_url',
            'link_type',
            'sort_order',
            'is_active',
            'start_at',
            'end_at',
        ]));

        return response()->json([
            'code' => 200,
            'message' => '轮播图更新成功',
            'data' => [
                'banner' => $banner,
            ],
        ]);
    }

    /**
     * 删除轮播图
     */
    public function destroy(int $id): JsonResponse
    {
        $banner = Banner::findOrFail($id);
        $banner->delete();

        return response()->json([
            'code' => 200,
            'message' => '轮播图删除成功',
        ]);
    }

    /**
     * 批量更新排序
     */
    public function updateOrder(Request $request): JsonResponse
    {
        $request->validate([
            'banners' => 'required|array',
            'banners.*.id' => 'required|integer|exists:banners,id',
            'banners.*.sort_order' => 'required|integer|min:0',
        ]);

        foreach ($request->input('banners') as $item) {
            Banner::where('id', $item['id'])->update([
                'sort_order' => $item['sort_order'],
            ]);
        }

        return response()->json([
            'code' => 200,
            'message' => '排序更新成功',
        ]);
    }
}
