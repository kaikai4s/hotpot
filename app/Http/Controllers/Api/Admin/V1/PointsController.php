<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin\V1;

use App\Http\Controllers\Controller;
use App\Models\MemberPoint;
use App\Models\PointTransaction;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PointsController extends Controller
{
    /**
     * 获取积分用户列表
     */
    public function index(Request $request): JsonResponse
    {
        // 使用 LEFT JOIN 确保显示所有用户，即使没有积分记录
        $query = User::leftJoin('member_points', 'users.id', '=', 'member_points.user_id')
            ->select(
                'users.id',
                'users.nickname',
                'users.phone',
                'users.avatar_url',
                'users.created_at as user_created_at',
                'users.updated_at as user_updated_at',
                'member_points.id as member_point_id',
                'member_points.total_points',
                'member_points.available_points',
                'member_points.frozen_points',
                'member_points.level',
                'member_points.created_at as member_point_created_at',
                'member_points.updated_at as member_point_updated_at'
            );

        // 搜索
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('users.nickname', 'like', "%{$search}%")
                  ->orWhere('users.phone', 'like', "%{$search}%");
            });
        }

        // 等级筛选
        if ($request->has('level')) {
            $query->where('member_points.level', $request->input('level'));
        }

        // 排序
        $sortBy = $request->input('sort_by', 'total_points');
        $sortOrder = $request->input('sort_order', 'desc');
        
        // 处理排序字段
        $sortFieldMap = [
            'total_points' => 'member_points.total_points',
            'available_points' => 'member_points.available_points',
            'frozen_points' => 'member_points.frozen_points',
            'level' => 'member_points.level',
        ];
        
        $sortField = $sortFieldMap[$sortBy] ?? 'member_points.total_points';
        $query->orderByRaw("COALESCE({$sortField}, 0) {$sortOrder}")
              ->orderBy('users.id', 'asc');

        $perPage = $request->input('per_page', 15);
        $results = $query->paginate($perPage);

        // 格式化返回数据，确保每个用户都有积分数据
        $formattedPoints = $results->getCollection()->map(function ($item) {
            return [
                'id' => $item->member_point_id ?? 0,
                'user_id' => $item->id,
                'total_points' => $item->total_points ?? 0,
                'available_points' => $item->available_points ?? 0,
                'frozen_points' => $item->frozen_points ?? 0,
                'level' => $item->level ?? 'bronze',
                'created_at' => $item->member_point_created_at ?? $item->user_created_at,
                'updated_at' => $item->member_point_updated_at ?? $item->user_updated_at,
                'user' => [
                    'id' => $item->id,
                    'nickname' => $item->nickname,
                    'phone' => $item->phone,
                    'avatar_url' => $item->avatar_url,
                ],
            ];
        });

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'points' => $formattedPoints->toArray(),
                'pagination' => [
                    'total' => $results->total(),
                    'per_page' => $results->perPage(),
                    'current_page' => $results->currentPage(),
                    'last_page' => $results->lastPage(),
                ],
            ],
        ]);
    }

    /**
     * 获取用户积分详情
     */
    public function show(int $userId): JsonResponse
    {
        $memberPoint = MemberPoint::with('user')->where('user_id', $userId)->firstOrFail();

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'member_point' => $memberPoint,
            ],
        ]);
    }

    /**
     * 获取用户积分交易记录
     */
    public function transactions(Request $request, int $userId): JsonResponse
    {
        $query = PointTransaction::where('user_id', $userId);

        // 类型筛选
        if ($request->has('type')) {
            $query->where('type', $request->input('type'));
        }

        // 日期范围
        if ($request->has('start_date')) {
            $query->whereDate('created_at', '>=', $request->input('start_date'));
        }
        if ($request->has('end_date')) {
            $query->whereDate('created_at', '<=', $request->input('end_date'));
        }

        $query->orderBy('created_at', 'desc');

        $perPage = $request->input('per_page', 15);
        $transactions = $query->paginate($perPage);

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'transactions' => $transactions->items(),
                'pagination' => [
                    'total' => $transactions->total(),
                    'per_page' => $transactions->perPage(),
                    'current_page' => $transactions->currentPage(),
                    'last_page' => $transactions->lastPage(),
                ],
            ],
        ]);
    }

    /**
     * 调整用户积分
     */
    public function adjust(Request $request, int $userId): JsonResponse
    {
        $request->validate([
            'points' => 'required|integer',
            'type' => 'required|in:earn,redeem,adjust',
            'description' => 'nullable|string|max:255',
        ]);

        DB::beginTransaction();
        try {
            $user = User::findOrFail($userId);
            $memberPoint = MemberPoint::firstOrCreate(
                ['user_id' => $userId],
                [
                    'total_points' => 0,
                    'available_points' => 0,
                    'frozen_points' => 0,
                    'level' => 'bronze',
                ]
            );

            $points = (int) $request->input('points');
            $type = $request->input('type');
            $description = $request->input('description', '管理员调整积分');

            // 更新积分
            if ($type === 'earn' || ($type === 'adjust' && $points > 0)) {
                $memberPoint->total_points += abs($points);
                $memberPoint->available_points += abs($points);
            } elseif ($type === 'redeem' || ($type === 'adjust' && $points < 0)) {
                $memberPoint->available_points = max(0, $memberPoint->available_points - abs($points));
            }

            // 更新等级
            $this->updateLevel($memberPoint);
            $memberPoint->save();

            // 创建交易记录
            PointTransaction::create([
                'user_id' => $userId,
                'type' => $type,
                'points' => $points,
                'balance_after' => $memberPoint->available_points,
                'source_type' => 'admin',
                'source_id' => auth('admin')->id(),
                'description' => $description,
            ]);

            DB::commit();

            return response()->json([
                'code' => 200,
                'message' => '积分调整成功',
                'data' => [
                    'member_point' => $memberPoint->fresh('user'),
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'code' => 500,
                'message' => '积分调整失败：' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 更新用户段位
     * 
     * 重要：段位判断基于 total_points（总积分），而不是 available_points（可用积分）
     * 总积分是用户累计获得的所有积分，不会因为积分兑换而减少
     * 可用积分会因兑换、过期等原因减少，但不影响段位判断
     * 
     * @param MemberPoint $memberPoint 用户积分记录
     */
    private function updateLevel(MemberPoint $memberPoint): void
    {
        // 使用总积分来判断段位，而不是可用积分
        // 这样即使积分被兑换或过期，段位也不会降低
        $totalPoints = $memberPoint->total_points;
        
        // 使用动态段位配置
        $newLevel = \App\Models\PointLevel::getLevelByPoints($totalPoints);
        $newLevelCode = $newLevel ? $newLevel->code : 'bronze';

        if ($memberPoint->level !== $newLevelCode) {
            $memberPoint->level = $newLevelCode;
        }
    }
}

