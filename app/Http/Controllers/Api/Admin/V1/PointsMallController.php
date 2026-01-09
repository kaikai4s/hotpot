<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin\V1;

use App\Http\Controllers\Controller;
use App\Services\PointsMallService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PointsMallController extends Controller
{
    public function __construct(
        private PointsMallService $mallService
    ) {
    }

    /**
     * 获取商品列表
     * GET /api/admin/v1/mall/products
     */
    public function index(Request $request): JsonResponse
    {
        $filters = [
            'type' => $request->input('type'),
            'status' => $request->input('status'),
            'keyword' => $request->input('keyword'),
        ];

        $perPage = min((int) $request->input('per_page', 20), 100);
        $products = $this->mallService->getProducts($filters, $perPage);

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'products' => $products->items(),
                'pagination' => [
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                    'per_page' => $products->perPage(),
                    'total' => $products->total(),
                ],
            ],
        ]);
    }

    /**
     * 获取商品详情
     * GET /api/admin/v1/mall/products/{id}
     */
    public function show(int $id): JsonResponse
    {
        $product = $this->mallService->getProduct($id);

        if (!$product) {
            return response()->json([
                'code' => 404,
                'message' => '商品不存在',
            ], 404);
        }

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'product' => $product,
            ],
        ]);
    }

    /**
     * 创建商品
     * POST /api/admin/v1/mall/products
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'image_url' => 'nullable|string|max:255',
            'type' => 'required|in:physical,experience',
            'points_required' => 'required|integer|min:1',
            'stock' => 'required|integer|min:0',
            'per_user_limit' => 'nullable|integer|min:0',
            'status' => 'nullable|in:active,inactive,sold_out',
        ]);

        try {
            $product = $this->mallService->createProduct($request->all());

            return response()->json([
                'code' => 200,
                'message' => '商品创建成功',
                'data' => [
                    'product' => $product,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => '商品创建失败: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 更新商品
     * PUT /api/admin/v1/mall/products/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $product = $this->mallService->getProduct($id);

        if (!$product) {
            return response()->json([
                'code' => 404,
                'message' => '商品不存在',
            ], 404);
        }

        $request->validate([
            'name' => 'sometimes|string|max:100',
            'description' => 'nullable|string|max:500',
            'image_url' => 'nullable|string|max:255',
            'type' => 'sometimes|in:physical,experience',
            'points_required' => 'sometimes|integer|min:1',
            'stock' => 'sometimes|integer|min:0',
            'per_user_limit' => 'nullable|integer|min:0',
            'status' => 'sometimes|in:active,inactive,sold_out',
        ]);

        try {
            $product = $this->mallService->updateProduct($product, $request->all());

            return response()->json([
                'code' => 200,
                'message' => '商品更新成功',
                'data' => [
                    'product' => $product,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => '商品更新失败: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 删除商品
     * DELETE /api/admin/v1/mall/products/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $product = $this->mallService->getProduct($id);

        if (!$product) {
            return response()->json([
                'code' => 404,
                'message' => '商品不存在',
            ], 404);
        }

        try {
            $this->mallService->deleteProduct($product);

            return response()->json([
                'code' => 200,
                'message' => '商品删除成功',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => '商品删除失败: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 更新商品状态
     * PUT /api/admin/v1/mall/products/{id}/status
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $product = $this->mallService->getProduct($id);

        if (!$product) {
            return response()->json([
                'code' => 404,
                'message' => '商品不存在',
            ], 404);
        }

        $request->validate([
            'status' => 'required|in:active,inactive,sold_out',
        ]);

        try {
            $product = $this->mallService->setProductStatus($product, $request->input('status'));

            return response()->json([
                'code' => 200,
                'message' => '状态更新成功',
                'data' => [
                    'product' => $product,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => '状态更新失败: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 获取兑换记录列表
     * GET /api/admin/v1/mall/redemptions
     */
    public function redemptions(Request $request): JsonResponse
    {
        $filters = [
            'status' => $request->input('status'),
            'per_page' => min((int) $request->input('per_page', 20), 100),
        ];
        
        // 传入 null 表示获取所有用户的兑换记录（管理端）
        $redemptions = $this->mallService->getUserRedemptions(null, $filters);

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'redemptions' => $redemptions->items(),
                'pagination' => [
                    'current_page' => $redemptions->currentPage(),
                    'last_page' => $redemptions->lastPage(),
                    'per_page' => $redemptions->perPage(),
                    'total' => $redemptions->total(),
                ],
            ],
        ]);
    }

    /**
     * 更新兑换状态
     * PUT /api/admin/v1/mall/redemptions/{id}/status
     */
    public function updateRedemptionStatus(Request $request, int $id): JsonResponse
    {
        $redemption = $this->mallService->getRedemption($id);

        if (!$redemption) {
            return response()->json([
                'code' => 404,
                'message' => '兑换记录不存在',
            ], 404);
        }

        $request->validate([
            'status' => 'required|in:pending,shipped,completed,cancelled',
            'tracking_number' => 'nullable|string|max:100',
        ]);

        try {
            $redemption = $this->mallService->updateRedemptionStatus(
                $redemption,
                $request->input('status'),
                $request->input('tracking_number')
            );

            return response()->json([
                'code' => 200,
                'message' => '状态更新成功',
                'data' => [
                    'redemption' => $redemption,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => '状态更新失败: ' . $e->getMessage(),
            ], 500);
        }
    }
}
