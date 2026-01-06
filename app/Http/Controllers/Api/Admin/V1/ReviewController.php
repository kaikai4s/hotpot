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
            'user_nickname' => 'nullable|string|max:64',
            'page' => 'nullable|integer|min:1',
            'page_size' => 'nullable|integer|min:1|max:100',
        ]);

        // 优化查询：只加载必要的关联字段，减少查询时间
        $query = Review::with([
            'user:id,nickname,avatar_url,equipped_title',
            'user.memberPoints',
            'dish:id,name',
            'order:id,order_no',
            'adminReplier:id,name',
            'adopter:id,name',
        ]);

        if ($request->filled('status')) {
            $query->where('reviews.status', $request->input('status'));
        }

        if ($request->filled('tracking_status')) {
            $query->where('reviews.tracking_status', $request->input('tracking_status'));
        }

        if ($request->has('is_adopted')) {
            // 使用 boolean() 方法正确处理字符串 "true"/"false" 和布尔值
            $query->where('reviews.is_adopted', $request->boolean('is_adopted'));
        }

        if ($request->filled('rating')) {
            $query->where('reviews.rating', $request->input('rating'));
        }

        // 用户昵称模糊筛选 - 优化：使用 whereHas 但添加索引提示，或使用子查询
        if ($request->filled('user_nickname')) {
            $userNickname = $request->input('user_nickname');
            // 使用子查询优化性能，避免 N+1 问题
            $userIds = \App\Models\User::where('nickname', 'like', '%' . $userNickname . '%')
                ->pluck('id')
                ->toArray();
            
            if (!empty($userIds)) {
                $query->whereIn('reviews.user_id', $userIds);
            } else {
                // 如果没有匹配的用户，返回空结果
                $query->whereRaw('1 = 0');
            }
        }

        $page = $request->input('page', 1);
        $pageSize = $request->input('page_size', 20);
        $reviews = $query->orderBy('reviews.created_at', 'desc')->paginate($pageSize, ['reviews.*'], 'page', $page);

        // 格式化评价数据，确保用户信息包含称号和段位
        $formattedReviews = $reviews->items();
        foreach ($formattedReviews as $review) {
            if ($review->user) {
                if (!$review->user->relationLoaded('memberPoints')) {
                    $review->user->load('memberPoints');
                }
                // 添加段位详细信息（包含颜色）
                if ($review->user->memberPoints) {
                    $levelModel = \App\Models\PointLevel::where('code', $review->user->memberPoints->level)->first();
                    if ($levelModel) {
                        $review->user->level = [
                            'code' => $levelModel->code,
                            'name' => $levelModel->name,
                            'icon' => $levelModel->icon,
                            'color' => $levelModel->color,
                        ];
                    }
                }
            }
        }

        // 统计未查看评价数量 - 优化：使用缓存或索引查询
        $unviewedCount = Review::where('is_viewed', false)->count();

        return response()->json([
            'code' => 200,
            'data' => [
                'reviews' => $formattedReviews,
                'pagination' => [
                    'current_page' => $reviews->currentPage(),
                    'total_pages' => $reviews->lastPage(),
                    'total_count' => $reviews->total(),
                    'page_size' => $reviews->perPage(),
                ],
                'unviewed_count' => $unviewedCount,
            ],
        ]);
    }

    public function show(int $reviewId): JsonResponse
    {
        $review = Review::with([
            'user:id,nickname,avatar_url,equipped_title',
            'user.memberPoints',
            'dish',
            'order',
            'adminReplier',
            'adopter',
        ])->findOrFail($reviewId);

        // 格式化用户信息，包含段位
        if ($review->user && $review->user->memberPoints) {
            $levelModel = \App\Models\PointLevel::where('code', $review->user->memberPoints->level)->first();
            if ($levelModel) {
                $review->user->level = [
                    'code' => $levelModel->code,
                    'name' => $levelModel->name,
                    'icon' => $levelModel->icon,
                    'color' => $levelModel->color,
                ];
            }
        }

        // 标记为已查看
        if (!$review->is_viewed) {
            $review->update([
                'is_viewed' => true,
                'viewed_at' => now(),
            ]);
            $review->refresh();
        }

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

    /**
     * 批量标记为已查看
     */
    public function markAsViewed(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:reviews,id',
        ]);

        try {
            $count = Review::whereIn('id', $request->input('ids'))
                ->where('is_viewed', false)
                ->update([
                    'is_viewed' => true,
                    'viewed_at' => now(),
                ]);

            return response()->json([
                'code' => 200,
                'message' => "成功标记 {$count} 条评价为已查看",
                'data' => [
                    'count' => $count,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => '操作失败：' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 批量删除评价
     */
    public function batchDelete(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:reviews,id',
        ]);

        try {
            $count = Review::whereIn('id', $request->input('ids'))->delete();

            return response()->json([
                'code' => 200,
                'message' => "成功删除 {$count} 条评价",
                'data' => [
                    'count' => $count,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => '删除失败：' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 更新评价的首页展示状态
     */
    public function updateFeatured(int $reviewId, Request $request): JsonResponse
    {
        $request->validate([
            'is_featured' => 'required|boolean',
        ]);

        try {
            $review = Review::findOrFail($reviewId);
            $review->update([
                'is_featured' => $request->boolean('is_featured'),
            ]);

            return response()->json([
                'code' => 200,
                'message' => $request->boolean('is_featured') ? '已设置为首页展示' : '已取消首页展示',
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
