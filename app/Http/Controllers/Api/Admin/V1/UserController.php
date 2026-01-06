<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * 获取用户列表（增强版）
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::with(['memberPoints', 'orders', 'reviews', 'userCoupons']);

        // 用户ID精确筛选（优先级最高）
        if ($request->filled('user_id')) {
            $query->where('id', $request->input('user_id'));
        }

        // 昵称精确筛选
        if ($request->filled('nickname')) {
            $query->where('nickname', $request->input('nickname'));
        }

        // 搜索（模糊搜索，仅在未指定精确筛选时使用）
        if ($request->has('search') && !$request->filled('user_id') && !$request->filled('nickname')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('nickname', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('openid', 'like', "%{$search}%");
            });
        }

        // 高级筛选
        // 注册时间范围
        if ($request->has('created_from')) {
            $query->where('created_at', '>=', $request->input('created_from'));
        }
        if ($request->has('created_to')) {
            $query->where('created_at', '<=', $request->input('created_to') . ' 23:59:59');
        }

        // 性别筛选
        if ($request->has('gender')) {
            $gender = $request->input('gender');
            if ($gender !== '') {
                $query->where('gender', $gender);
            }
        }

        // 手机号筛选
        if ($request->has('has_phone')) {
            $hasPhone = $request->input('has_phone');
            if ($hasPhone === '1') {
                $query->whereNotNull('phone')->where('phone', '!=', '');
            } elseif ($hasPhone === '0') {
                $query->where(function ($q) {
                    $q->whereNull('phone')->orWhere('phone', '');
                });
            }
        }

        // 积分范围筛选
        if ($request->has('min_points') || $request->has('max_points')) {
            $query->whereHas('memberPoints', function ($q) use ($request) {
                if ($request->has('min_points')) {
                    $q->where('total_points', '>=', $request->input('min_points'));
                }
                if ($request->has('max_points')) {
                    $q->where('total_points', '<=', $request->input('max_points'));
                }
            });
        }

        // 订单数筛选
        if ($request->has('min_orders') || $request->has('max_orders')) {
            $query->withCount('orders');
            if ($request->has('min_orders')) {
                $query->having('orders_count', '>=', $request->input('min_orders'));
            }
            if ($request->has('max_orders')) {
                $query->having('orders_count', '<=', $request->input('max_orders'));
            }
        } else {
            // 默认加载计数，用于显示
            $query->withCount(['orders', 'reviews']);
        }

        // 排序
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        
        // 特殊排序处理
        if ($sortBy === 'total_points') {
            $query->leftJoin('member_points', 'users.id', '=', 'member_points.user_id')
                  ->orderBy('member_points.total_points', $sortOrder)
                  ->select('users.*')
                  ->groupBy('users.id', 'users.openid', 'users.unionid', 'users.nickname', 'users.avatar_url', 'users.phone', 'users.gender', 'users.created_at', 'users.updated_at');
        } elseif ($sortBy === 'orders_count') {
            if (!$query->getQuery()->columns || !in_array('orders_count', $query->getQuery()->columns)) {
                $query->withCount('orders');
            }
            $query->orderBy('orders_count', $sortOrder);
        } elseif ($sortBy === 'reviews_count') {
            if (!$query->getQuery()->columns || !in_array('reviews_count', $query->getQuery()->columns)) {
                $query->withCount('reviews');
            }
            $query->orderBy('reviews_count', $sortOrder);
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

        // 分页
        $perPage = $request->input('per_page', 15);
        $users = $query->paginate($perPage);

        // 为每个用户添加统计信息
        $users->getCollection()->transform(function ($user) {
            $memberPoint = $user->memberPoints;
            $orders = $user->orders ?? collect();
            
            // 获取段位详细信息
            $levelInfo = null;
            if ($memberPoint && $memberPoint->level) {
                $levelModel = \App\Models\PointLevel::where('code', $memberPoint->level)->first();
                if ($levelModel) {
                    $levelInfo = [
                        'code' => $levelModel->code,
                        'name' => $levelModel->name,
                        'icon' => $levelModel->icon,
                        'color' => $levelModel->color,
                    ];
                }
            }
            
            return [
                'id' => $user->id,
                'openid' => $user->openid,
                'unionid' => $user->unionid,
                'nickname' => $user->nickname,
                'avatar_url' => $user->avatar_url,
                'equipped_title' => $user->equipped_title,
                'phone' => $user->phone,
                'gender' => $user->gender,
                'is_active' => $user->is_active ?? true,
                'remark' => $user->remark ?? null,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
                'member_points' => $memberPoint ? [
                    'level' => $memberPoint->level,
                    'level_info' => $levelInfo,
                ] : null,
                'statistics' => [
                    'total_points' => $memberPoint?->total_points ?? 0,
                    'available_points' => $memberPoint?->available_points ?? 0,
                    'level' => $memberPoint?->level ?? 'heitie',
                    'orders_count' => $user->orders_count ?? $orders->count(),
                    'reviews_count' => $user->reviews_count ?? $user->reviews->count(),
                    'coupons_count' => $user->userCoupons->count(),
                    'total_spent' => $orders->whereIn('status', ['paid', 'completed', 'pending_review'])->sum(function ($order) {
                        return $order->final_amount ?? $order->total_amount ?? 0;
                    }),
                ],
            ];
        });

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'users' => $users->items(),
                'pagination' => [
                    'total' => $users->total(),
                    'per_page' => $users->perPage(),
                    'current_page' => $users->currentPage(),
                    'last_page' => $users->lastPage(),
                ],
            ],
        ]);
    }

    /**
     * 获取用户详情（增强版）
     */
    public function show(int $id): JsonResponse
    {
        $user = User::with([
            'memberPoints',
            'orders' => function ($query) {
                $query->with(['items.dish', 'items.combo', 'table'])
                      ->orderBy('created_at', 'desc');
            },
            'reviews' => function ($query) {
                $query->orderBy('created_at', 'desc')->limit(10);
            },
            'userCoupons' => function ($query) {
                $query->with('coupon')->orderBy('created_at', 'desc')->limit(10);
            },
            'pointTransactions' => function ($query) {
                $query->orderBy('created_at', 'desc')->limit(20);
            },
            'reservations' => function ($query) {
                $query->orderBy('created_at', 'desc')->limit(10);
            },
            // 邀请信息
            'invitations' => function ($query) {
                $query->with(['invitee' => function ($q) {
                    $q->with('memberPoints');
                }])->orderBy('created_at', 'desc');
            },
            'inviter' => function ($query) {
                $query->with('memberPoints');
            },
            'invitees' => function ($query) {
                $query->with('memberPoints')->orderBy('created_at', 'desc');
            },
            // 成就信息
            'achievements' => function ($query) {
                $query->with(['achievementTemplate'])
                      ->whereNotNull('completed_at')
                      ->orderBy('completed_at', 'desc');
            },
        ])->findOrFail($id);

        $memberPoint = $user->memberPoints;
        
        // 计算统计数据
        $statistics = [
            'total_points' => $memberPoint?->total_points ?? 0,
            'available_points' => $memberPoint?->available_points ?? 0,
            'frozen_points' => $memberPoint?->frozen_points ?? 0,
            'level' => $memberPoint?->level ?? 'heitie',
            'orders_count' => $user->orders()->count(),
            'orders_total_amount' => $user->orders()->whereIn('status', ['paid', 'completed', 'pending_review'])->sum('final_amount') ?? 0,
            'reviews_count' => $user->reviews()->count(),
            'approved_reviews_count' => $user->reviews()->where('status', 'approved')->count(),
            'coupons_count' => $user->userCoupons()->count(),
            'unused_coupons_count' => $user->userCoupons()->where('status', 'unused')->count(),
            'reservations_count' => $user->reservations()->count(),
            'point_earned_total' => $user->pointTransactions()->where('type', 'earn')->sum('points') ?? 0,
            'point_redeemed_total' => abs($user->pointTransactions()->where('type', 'redeem')->sum('points') ?? 0),
            // 邀请统计
            'invitations_count' => $user->invitations()->count(),
            'successful_invitations_count' => $user->invitations()->where('status', 'completed')->count(),
            'invitees_count' => $user->invitees()->count(),
            // 成就统计
            'achievements_count' => $user->achievements()->whereNotNull('completed_at')->count(),
            'total_achievements_count' => \App\Models\AchievementTemplate::where('is_active', true)->count(),
        ];

        // 获取段位信息
        $levelInfo = null;
        if ($memberPoint && $memberPoint->level) {
            $levelModel = \App\Models\PointLevel::where('code', $memberPoint->level)->first();
            if ($levelModel) {
                $levelInfo = [
                    'id' => $levelModel->id,
                    'code' => $levelModel->code,
                    'name' => $levelModel->name,
                    'icon' => $levelModel->icon,
                    'color' => $levelModel->color,
                    'min_points' => $levelModel->min_points,
                    'description' => $levelModel->description,
                ];
            }
        }

        // 获取所有订单（用于详情显示）
        $allOrders = $user->orders()->with(['items.dish', 'items.combo', 'table'])
            ->orderBy('created_at', 'desc')
            ->get();

        // 获取所有成就（包括未完成的）
        $allAchievements = \App\Models\UserAchievement::where('user_id', $user->id)
            ->with('achievementTemplate')
            ->orderByRaw('completed_at IS NULL ASC') // 先显示已完成的
            ->orderBy('completed_at', 'desc')
            ->get()
            ->map(function ($achievement) {
                // 处理进度数据，确保前端可以正确访问
                $progress = $achievement->progress ?? ['current' => 0, 'target' => 0];
                return [
                    'id' => $achievement->id,
                    'user_id' => $achievement->user_id,
                    'achievement_template_id' => $achievement->achievement_template_id,
                    'is_completed' => !is_null($achievement->completed_at),
                    'current_progress' => $progress['current'] ?? 0,
                    'target_progress' => $progress['target'] ?? 0,
                    'completed_at' => $achievement->completed_at?->toDateTimeString(),
                    'reward_issued' => $achievement->reward_issued,
                    'created_at' => $achievement->created_at->toDateTimeString(),
                    'updated_at' => $achievement->updated_at->toDateTimeString(),
                    'achievement_template' => $achievement->achievementTemplate ? [
                        'id' => $achievement->achievementTemplate->id,
                        'name' => $achievement->achievementTemplate->name,
                        'description' => $achievement->achievementTemplate->description,
                        'icon' => $achievement->achievementTemplate->icon,
                        'category' => $achievement->achievementTemplate->category,
                    ] : null,
                ];
            });

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'user' => $user,
                'statistics' => $statistics,
                'level_info' => $levelInfo,
                'all_orders' => $allOrders,
                'all_achievements' => $allAchievements,
            ],
        ]);
    }

    /**
     * 更新用户信息（增强版）
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        $request->validate([
            'nickname' => 'sometimes|string|max:64',
            'phone' => 'sometimes|nullable|string|max:20',
            'gender' => 'sometimes|nullable|integer|in:0,1,2',
            'is_active' => 'sometimes|boolean',
            'remark' => 'sometimes|nullable|string|max:500',
        ]);

        $updateData = $request->only([
            'nickname',
            'phone',
            'gender',
        ]);

        // 如果模型有 is_active 和 remark 字段，添加它们
        if ($request->has('is_active')) {
            $updateData['is_active'] = $request->input('is_active');
        }
        if ($request->has('remark')) {
            $updateData['remark'] = $request->input('remark');
        }

        $user->update($updateData);

        return response()->json([
            'code' => 200,
            'message' => '用户信息更新成功',
            'data' => [
                'user' => $user->fresh(),
            ],
        ]);
    }

    /**
     * 批量删除用户
     */
    public function batchDestroy(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:users,id',
        ]);

        $ids = $request->input('ids');
        $deleted = 0;
        $failed = [];

        foreach ($ids as $id) {
            $user = User::find($id);
            if (!$user) {
                $failed[] = $id;
                continue;
            }

            // 检查是否有关联数据
            if ($user->reservations()->exists() || $user->reviews()->exists() || $user->orders()->exists()) {
                $failed[] = $id;
                continue;
            }

            $user->delete();
            $deleted++;
        }

        return response()->json([
            'code' => 200,
            'message' => "成功删除 {$deleted} 个用户" . (count($failed) > 0 ? "，{$failed} 个用户因有关联数据无法删除" : ''),
            'data' => [
                'deleted' => $deleted,
                'failed' => $failed,
            ],
        ]);
    }

    /**
     * 获取用户统计概览
     */
    public function statistics(): JsonResponse
    {
        $totalUsers = User::count();
        $todayUsers = User::whereDate('created_at', today())->count();
        $thisMonthUsers = User::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
        
        $usersWithPhone = User::whereNotNull('phone')->where('phone', '!=', '')->count();
        $usersWithOrders = User::has('orders')->count();
        $usersWithPoints = \App\Models\MemberPoint::where('total_points', '>', 0)->count();

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'statistics' => [
                    'total_users' => $totalUsers,
                    'today_users' => $todayUsers,
                    'this_month_users' => $thisMonthUsers,
                    'users_with_phone' => $usersWithPhone,
                    'users_with_orders' => $usersWithOrders,
                    'users_with_points' => $usersWithPoints,
                ],
            ],
        ]);
    }

    /**
     * 删除用户
     */
    public function destroy(int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        // 检查是否有关联数据
        if ($user->reservations()->exists() || $user->reviews()->exists()) {
            return response()->json([
                'code' => 409,
                'message' => '该用户存在关联数据，无法删除',
            ], 409);
        }

        $user->delete();

        return response()->json([
            'code' => 200,
            'message' => '用户删除成功',
        ]);
    }
}
