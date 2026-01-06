<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\AchievementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AchievementController extends Controller
{
    public function __construct(
        private AchievementService $achievementService
    ) {
    }

    /**
     * 获取成就列表
     */
    public function index(Request $request): JsonResponse
    {
        $startTime = microtime(true);
        
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'code' => 401,
                'message' => '未登录',
            ], 401);
        }

        \Illuminate\Support\Facades\Log::info('【成就API-开始】', [
            'user_id' => $user->id,
            'user_nickname' => $user->nickname,
            'category' => $request->input('category'),
        ]);

        $category = $request->input('category'); // consume, review, invite, checkin, points
        $achievements = $this->achievementService->getUserAchievements($user, $category);
        
        $apiTime = microtime(true) - $startTime;
        \Illuminate\Support\Facades\Log::info('【成就API-完成】', [
            'user_id' => $user->id,
            'api_time' => round($apiTime, 3),
            'achievements_count' => count($achievements),
        ]);

        // 统计完成数量
        $completedCount = 0;
        $totalCount = count($achievements);
        foreach ($achievements as $achievement) {
            if ($achievement['completed_at']) {
                $completedCount++;
            }
        }

        // 直接返回成就列表（不分组），前端会处理分类筛选
        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'achievements' => $achievements,
                'statistics' => [
                    'completed' => $completedCount,
                    'total' => $totalCount,
                    'progress' => $totalCount > 0 ? round(($completedCount / $totalCount) * 100, 1) : 0,
                ],
            ],
        ]);
    }

    /**
     * 获取成就详情
     */
    public function show(int $id): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'code' => 401,
                'message' => '未登录',
            ], 401);
        }

        $achievement = \App\Models\UserAchievement::where('id', $id)
            ->where('user_id', $user->id)
            ->with('achievementTemplate')
            ->first();

        if (!$achievement) {
            return response()->json([
                'code' => 404,
                'message' => '成就不存在',
            ], 404);
        }

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => $achievement,
        ]);
    }

    /**
     * 佩戴称号
     */
    public function equipTitle(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'code' => 401,
                'message' => '未登录',
            ], 401);
        }

        $request->validate([
            'achievement_id' => 'required|integer|exists:user_achievements,id',
        ]);

        $userAchievement = \App\Models\UserAchievement::where('id', $request->input('achievement_id'))
            ->where('user_id', $user->id)
            ->with('achievementTemplate')
            ->first();

        if (!$userAchievement) {
            return response()->json([
                'code' => 404,
                'message' => '成就不存在',
            ], 404);
        }

        if (!$userAchievement->completed_at) {
            return response()->json([
                'code' => 400,
                'message' => '该成就尚未完成，无法佩戴',
            ], 400);
        }

        $user->update([
            'equipped_title' => $userAchievement->achievementTemplate->name,
        ]);

        return response()->json([
            'code' => 200,
            'message' => '称号佩戴成功',
            'data' => [
                'equipped_title' => $user->equipped_title,
            ],
        ]);
    }

    /**
     * 卸下称号
     */
    public function unequipTitle(): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'code' => 401,
                'message' => '未登录',
            ], 401);
        }

        $user->update([
            'equipped_title' => null,
        ]);

        return response()->json([
            'code' => 200,
            'message' => '称号已卸下',
            'data' => [
                'equipped_title' => null,
            ],
        ]);
    }
}

