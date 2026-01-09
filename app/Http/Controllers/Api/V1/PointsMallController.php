<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\PointsMallService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PointsMallController extends Controller
{
    public function __construct(
        private PointsMallService $mallService
    ) {
    }

    /**
     * 获取商品列表
     * GET /api/v1/mall/products
     */
    public function index(Request $request): JsonResponse
    {
        $filters = [
            'type' => $request->input('type'),
            'status' => 'active', // 前台只显示上架商品
            'min_points' => $request->input('min_points'),
            'max_points' => $request->input('max_points'),
        ];

        $perPage = min((int) $request->input('per_page', 20), 50);
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
     * GET /api/v1/mall/products/{id}
     */
    public function show(int $id): JsonResponse
    {
        $product = $this->mallService->getProduct($id);

        if (!$product || $product->status !== 'active') {
            return response()->json([
                'code' => 404,
                'message' => '商品不存在或已下架',
            ], 404);
        }

        $user = Auth::user();
        $canRedeem = false;
        $redeemMessage = null;

        if ($user) {
            $redeemCheck = $this->mallService->canRedeem($user, $product);
            $canRedeem = $redeemCheck['can'];
            $redeemMessage = $redeemCheck['reason'] ?? null;
        }

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'product' => $product,
                'can_redeem' => $canRedeem,
                'redeem_message' => $redeemMessage,
            ],
        ]);
    }

    /**
     * 兑换商品
     * POST /api/v1/mall/products/{id}/redeem
     */
    public function redeem(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json([
                'code' => 401,
                'message' => '未登录',
            ], 401);
        }

        $product = $this->mallService->getProduct($id);

        if (!$product || $product->status !== 'active') {
            return response()->json([
                'code' => 404,
                'message' => '商品不存在或已下架',
            ], 404);
        }

        // 验证收货地址（实物商品需要）
        $shippingAddress = null;
        if ($product->type === 'physical') {
            $request->validate([
                'shipping_address' => 'required|array',
                'shipping_address.name' => 'required|string|max:50',
                'shipping_address.phone' => 'required|string|max:20',
                'shipping_address.address' => 'required|string|max:200',
            ]);
            $shippingAddress = $request->input('shipping_address');
        }

        try {
            $data = [];
            if ($shippingAddress) {
                $data['shipping_address'] = $shippingAddress;
            }
            $redemption = $this->mallService->redeemProduct($user, $product, $data);

            return response()->json([
                'code' => 200,
                'message' => '兑换成功',
                'data' => [
                    'redemption_id' => $redemption->id,
                    'points_used' => $redemption->points_used,
                    'status' => $redemption->status,
                ],
            ]);
        } catch (\Exception $e) {
            $code = $e->getCode() ?: 400;
            return response()->json([
                'code' => $code,
                'message' => $e->getMessage(),
            ], $code >= 400 && $code < 600 ? $code : 400);
        }
    }

    /**
     * 获取用户兑换记录
     * GET /api/v1/mall/redemptions
     */
    public function redemptions(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json([
                'code' => 401,
                'message' => '未登录',
            ], 401);
        }

        $filters = [
            'status' => $request->input('status'),
            'per_page' => min((int) $request->input('per_page', 20), 50),
        ];
        
        $redemptions = $this->mallService->getUserRedemptions($user, $filters);

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
     * 获取兑换详情
     * GET /api/v1/mall/redemptions/{id}
     */
    public function redemptionDetail(int $id): JsonResponse
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json([
                'code' => 401,
                'message' => '未登录',
            ], 401);
        }

        $redemption = $this->mallService->getRedemption($id);

        if (!$redemption || $redemption->user_id !== $user->id) {
            return response()->json([
                'code' => 404,
                'message' => '兑换记录不存在',
            ], 404);
        }

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'redemption' => $redemption->load('product'),
            ],
        ]);
    }
}
