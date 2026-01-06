<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Dish;
use App\Models\User;
use App\Services\ReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ReviewController extends Controller
{
    public function __construct(
        private ReviewService $reviewService
    ) {
    }

    public function create(Request $request): JsonResponse
    {
        $request->validate([
            'order_id' => 'required|integer|exists:orders,id',
            'dish_id' => 'required|integer|exists:dishes,id',
            'rating' => 'required|integer|min:1|max:5',
            'content' => 'nullable|string|max:500',
            'images' => 'nullable|array|max:3',
            'images.*' => 'url|max:255',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:20',
        ]);

        try {
            /** @var User|null $user */
            $user = Auth::user();
            if (!$user || !($user instanceof User)) {
                return response()->json([
                    'code' => 401,
                    'message' => '未登录',
                ], 401);
            }
            $review = $this->reviewService->createReview(
                $user,
                $request->input('order_id'),
                $request->input('dish_id'),
                $request->input('rating'),
                $request->input('content'),
                $request->input('images'),
                $request->input('tags')
            );

            return response()->json([
                'code' => 201,
                'message' => '评价提交成功',
                'data' => [
                    'review_id' => $review->id,
                    'status' => $review->status,
                    'submitted_at' => $review->created_at->format('Y-m-d H:i:s'),
                ],
            ], 201);
        } catch (\Exception $e) {
            $code = $e->getCode() ?: 500;
            // 生产环境不暴露详细错误信息
            $message = config('app.debug') ? $e->getMessage() : '操作失败，请稍后重试';
            return response()->json([
                'code' => $code,
                'message' => $message,
            ], $code >= 400 && $code < 600 ? $code : 500);
        }
    }

    public function getDishReviews(int $dishId, Request $request): JsonResponse
    {
        $request->validate([
            'page' => 'nullable|integer|min:1',
            'page_size' => 'nullable|integer|min:1|max:50',
            'sort' => 'nullable|in:latest,rating_desc,rating_asc',
        ]);

        $dish = Dish::findOrFail($dishId);
        $page = $request->input('page', 1);
        $pageSize = $request->input('page_size', 10);
        $sort = $request->input('sort', 'latest');

        $query = $dish->reviews()->where('status', 'approved');

        switch ($sort) {
            case 'rating_desc':
                $query->orderBy('rating', 'desc');
                break;
            case 'rating_asc':
                $query->orderBy('rating', 'asc');
                break;
            default:
                $query->orderBy('created_at', 'desc');
        }

        $reviews = $query->with([
            'user:id,nickname,avatar_url,equipped_title',
            'user.memberPoints',
        ])->paginate($pageSize, ['*'], 'page', $page);

        $ratingDistribution = $dish->reviews()
            ->where('status', 'approved')
            ->selectRaw('rating, COUNT(*) as count')
            ->groupBy('rating')
            ->pluck('count', 'rating')
            ->toArray();

        // 格式化评价数据，包含用户称号和段位
        $formattedReviews = $reviews->items();
        foreach ($formattedReviews as $review) {
            if ($review->user) {
                $review->user->load('memberPoints');
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

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'reviews' => $formattedReviews,
                'pagination' => [
                    'current_page' => $reviews->currentPage(),
                    'total_pages' => $reviews->lastPage(),
                    'total_count' => $reviews->total(),
                    'page_size' => $reviews->perPage(),
                ],
                'summary' => [
                    'average_rating' => (float) $dish->average_rating,
                    'total_reviews' => $dish->review_count,
                    'rating_distribution' => $ratingDistribution,
                ],
            ],
        ]);
    }

    /**
     * 获取所有评价（用户可见）
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'status' => 'nullable|in:pending,approved,rejected',
            'tracking_status' => 'nullable|in:pending,in_progress,completed,cancelled',
            'is_adopted' => 'nullable', // 移除 boolean 验证，使用 $request->has() 和 $request->boolean() 处理
            'my_reviews' => 'nullable', // 移除 boolean 验证，使用 $request->boolean() 处理
            'page' => 'nullable|integer|min:1',
            'page_size' => 'nullable|integer|min:1|max:50',
        ]);

        $query = \App\Models\Review::with([
            'user:id,nickname,avatar_url,equipped_title',
            'user.memberPoints',
            'dish',
            'order',
            'adminReplier',
            'adopter',
        ]);

        // 如果请求当前用户的评价，则不过滤状态（显示所有状态的评价）
        // 否则只显示已审核通过的评价
        if ($request->boolean('my_reviews')) {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'code' => 401,
                    'message' => '未登录',
                ], 401);
            }
            $query->where('user_id', $user->id);
            // 当前用户的评价可以显示所有状态
            if ($request->filled('status')) {
                $query->where('status', $request->input('status'));
            }
        } else {
            // 公开评价只显示已审核通过的
            $query->where('status', 'approved');
        }

        if ($request->filled('tracking_status')) {
            $query->where('tracking_status', $request->input('tracking_status'));
        }

        if ($request->has('is_adopted')) {
            // 使用 boolean() 方法正确处理字符串 "true"/"false" 和布尔值
            $query->where('is_adopted', $request->boolean('is_adopted'));
        }

        $page = $request->input('page', 1);
        $pageSize = $request->input('page_size', 20);
        $reviews = $query->orderBy('created_at', 'desc')->paginate($pageSize, ['*'], 'page', $page);

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

        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => [
                'reviews' => $formattedReviews,
                'pagination' => [
                    'current_page' => $reviews->currentPage(),
                    'total_pages' => $reviews->lastPage(),
                    'total_count' => $reviews->total(),
                    'page_size' => $reviews->perPage(),
                ],
            ],
        ]);
    }

    /**
     * 获取追踪优化的评价
     */
    public function getTrackingReviews(Request $request): JsonResponse
    {
        $request->validate([
            'tracking_status' => 'nullable|in:pending,in_progress,completed,cancelled',
            'page' => 'nullable|integer|min:1',
            'page_size' => 'nullable|integer|min:1|max:50',
        ]);

        $query = \App\Models\Review::with([
            'user:id,nickname,avatar_url,equipped_title',
            'user.memberPoints',
            'dish',
            'order',
            'adminReplier',
            'adopter',
        ])
            ->where('status', 'approved')
            ->where('is_adopted', true)
            ->where('tracking_status', '!=', 'cancelled');

        if ($request->filled('tracking_status')) {
            $query->where('tracking_status', $request->input('tracking_status'));
        }

        $page = $request->input('page', 1);
        $pageSize = $request->input('page_size', 20);
        $reviews = $query->orderBy('adopted_at', 'desc')->paginate($pageSize, ['*'], 'page', $page);

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

        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => [
                'reviews' => $formattedReviews,
                'pagination' => [
                    'current_page' => $reviews->currentPage(),
                    'total_pages' => $reviews->lastPage(),
                    'total_count' => $reviews->total(),
                    'page_size' => $reviews->perPage(),
                ],
            ],
        ]);
    }

    /**
     * 获取首页展示的评价
     */
    public function getFeaturedReviews(): JsonResponse
    {
        $reviews = \App\Models\Review::with(['user:id,nickname,avatar_url,equipped_title'])
            ->where('status', 'approved')
            ->where('is_featured', true)
            ->orderBy('created_at', 'desc')
            ->limit(6)
            ->get();

        // 格式化评价数据
        $formattedReviews = $reviews->map(function ($review) {
            return [
                'id' => $review->id,
                'user_id' => $review->user_id,
                'rating' => $review->rating,
                'content' => $review->content,
                'created_at' => $review->created_at->toISOString(),
                'user' => $review->user ? [
                    'id' => $review->user->id,
                    'nickname' => $review->user->nickname,
                    'avatar_url' => $review->user->avatar_url,
                    'equipped_title' => $review->user->equipped_title,
                ] : null,
            ];
        });

        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => [
                'reviews' => $formattedReviews,
            ],
        ]);
    }

    /**
     * 获取评价详情（包含订单中的所有菜品信息）
     */
    public function show(int $reviewId): JsonResponse
    {
        $review = \App\Models\Review::with([
            'user:id,nickname,avatar_url,equipped_title',
            'user.memberPoints',
            'dish',
            'order.items.dish',
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

        // 获取订单中的所有菜品信息（用于显示评价了哪些菜品）
        $orderDishes = [];
        if ($review->order && $review->order->items) {
            $orderDishes = $review->order->items->map(function ($item) use ($review) {
                return [
                    'dish_id' => $item->dish_id,
                    'dish_name' => $item->dish->name ?? "菜品 {$item->dish_id}",
                    'quantity' => $item->quantity,
                    'is_reviewed' => $item->dish_id === $review->dish_id, // 当前评价的菜品
                ];
            })->toArray();
        }

        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => [
                'review' => $review,
                'order_dishes' => $orderDishes, // 订单中的所有菜品
            ],
        ]);
    }
}

