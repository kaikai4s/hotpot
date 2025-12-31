<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin\V1;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Services\ReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    public function __construct(
        private ReviewService $reviewService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'status' => 'nullable|in:pending,approved,rejected',
            'tracking_status' => 'nullable|in:pending,in_progress,completed,cancelled',
            'is_adopted' => 'nullable', // 移除 boolean 验证，使用 $request->boolean() 处理
            'rating' => 'nullable|integer|min:1|max:5',
            'page' => 'nullable|integer|min:1',
            'page_size' => 'nullable|integer|min:1|max:100',
        ]);

        $query = Review::with(['user', 'dish', 'order', 'adminReplier', 'adopter']);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('tracking_status')) {
            $query->where('tracking_status', $request->input('tracking_status'));
        }

        if ($request->has('is_adopted')) {
            // 使用 boolean() 方法正确处理字符串 "true"/"false" 和布尔值
            $query->where('is_adopted', $request->boolean('is_adopted'));
        }

        if ($request->filled('rating')) {
            $query->where('rating', $request->input('rating'));
        }

        $page = $request->input('page', 1);
        $pageSize = $request->input('page_size', 20);
        $reviews = $query->orderBy('created_at', 'desc')->paginate($pageSize, ['*'], 'page', $page);

        return response()->json([
            'code' => 200,
            'data' => [
                'reviews' => $reviews->items(),
                'pagination' => [
                    'current_page' => $reviews->currentPage(),
                    'total_pages' => $reviews->lastPage(),
                    'total_count' => $reviews->total(),
                    'page_size' => $reviews->perPage(),
                ],
            ],
        ]);
    }

    public function show(int $reviewId): JsonResponse
    {
        $review = Review::with(['user', 'dish', 'order', 'adminReplier', 'adopter'])
            ->findOrFail($reviewId);

        return response()->json([
            'code' => 200,
            'data' => $review,
        ]);
    }

    public function approve(int $reviewId, Request $request): JsonResponse
    {
        $request->validate([
            'action' => 'required|in:approve,reject',
            'reason' => 'nullable|string|max:255',
        ]);

        try {
            if ($request->input('action') === 'approve') {
                $review = $this->reviewService->approveReview($reviewId);
            } else {
                $review = $this->reviewService->rejectReview($reviewId, $request->input('reason'));
            }

            return response()->json([
                'code' => 200,
                'message' => '审核完成',
                'data' => $review->load(['user', 'dish', 'order', 'adminReplier', 'adopter']),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 404,
                'message' => $e->getMessage() ?: '评价不存在',
            ], 404);
        }
    }

    /**
     * 回复评价
     */
    public function reply(int $reviewId, Request $request): JsonResponse
    {
        $request->validate([
            'reply' => 'required|string|max:1000',
        ]);

        try {
            $admin = Auth::guard('admin')->user();
            if (!$admin) {
                return response()->json([
                    'code' => 401,
                    'message' => '未登录',
                ], 401);
            }
            $review = $this->reviewService->replyReview($reviewId, $admin, $request->input('reply'));

            return response()->json([
                'code' => 200,
                'message' => '回复成功',
                'data' => $review->load(['user', 'dish', 'order', 'adminReplier', 'adopter']),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 404,
                'message' => $e->getMessage() ?: '评价不存在',
            ], 404);
        }
    }

    /**
     * 采纳评价建议
     */
    public function adopt(int $reviewId): JsonResponse
    {
        try {
            $admin = Auth::guard('admin')->user();
            if (!$admin) {
                return response()->json([
                    'code' => 401,
                    'message' => '未登录',
                ], 401);
            }
            $review = $this->reviewService->adoptReview($reviewId, $admin);

            return response()->json([
                'code' => 200,
                'message' => '评价建议已采纳',
                'data' => $review->load(['user', 'dish', 'order', 'adminReplier', 'adopter']),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 400,
                'message' => $e->getMessage() ?: '操作失败',
            ], 400);
        }
    }

    /**
     * 更新追踪状态
     */
    public function updateTracking(int $reviewId, Request $request): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:pending,in_progress,completed,cancelled',
            'message' => 'nullable|string|max:500',
        ]);

        try {
            $admin = Auth::guard('admin')->user();
            if (!$admin) {
                return response()->json([
                    'code' => 401,
                    'message' => '未登录',
                ], 401);
            }
            $review = $this->reviewService->updateTrackingStatus(
                $reviewId,
                $admin,
                $request->input('status'),
                $request->input('message')
            );

            return response()->json([
                'code' => 200,
                'message' => '追踪状态更新成功',
                'data' => $review->load(['user', 'dish', 'order', 'adminReplier', 'adopter']),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 400,
                'message' => $e->getMessage() ?: '操作失败',
            ], 400);
        }
    }

    /**
     * 添加追踪更新
     */
    public function addTrackingUpdate(int $reviewId, Request $request): JsonResponse
    {
        $request->validate([
            'message' => 'required|string|max:500',
        ]);

        try {
            $admin = Auth::guard('admin')->user();
            if (!$admin) {
                return response()->json([
                    'code' => 401,
                    'message' => '未登录',
                ], 401);
            }
            $review = $this->reviewService->addTrackingUpdate($reviewId, $admin, $request->input('message'));

            return response()->json([
                'code' => 200,
                'message' => '追踪更新添加成功',
                'data' => $review->load(['user', 'dish', 'order', 'adminReplier', 'adopter']),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 404,
                'message' => $e->getMessage() ?: '评价不存在',
            ], 404);
        }
    }
}
