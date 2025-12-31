<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PointLevel;
use App\Services\PointService;
use App\Services\PointExpirationService;
use App\Services\PointRuleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PointController extends Controller
{
    public function __construct(
        private PointService $pointService,
        private PointExpirationService $expirationService,
        private PointRuleService $pointRuleService
    ) {
    }

    public function getPoints(): JsonResponse
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json([
                'code' => 401,
                'message' => '未登录',
            ], 401);
        }
        
        // 调试日志：记录当前用户信息
        Log::info('前台获取积分 - 用户ID: ' . $user->id . ', 昵称: ' . ($user->nickname ?? 'N/A'));
        
        $memberPoint = $this->pointService->getPoints($user);
        
        // 调试日志：记录积分数据
        Log::info('前台获取积分 - 用户ID: ' . $user->id . ', 总积分: ' . $memberPoint->total_points . ', 可用积分: ' . $memberPoint->available_points);

        // 计算到下一级所需积分
        $pointsToNextLevel = $this->calculatePointsToNextLevel($memberPoint->level, $memberPoint->total_points);

        // 获取即将过期的积分
        $expiringPoints = $this->expirationService->getExpiringPoints($user, 30);
        $totalExpiring = $this->expirationService->getExpiringPointsTotal($user, 30);

        // 获取当前段位信息（从数据库动态获取）
        $currentLevel = \App\Models\PointLevel::where('code', $memberPoint->level)
            ->where('is_active', true)
            ->first();

        // 获取下一级段位信息
        $nextLevel = null;
        if ($currentLevel) {
            $nextLevel = \App\Models\PointLevel::getActiveLevels()
                ->where('min_points', '>', $currentLevel->min_points)
                ->sortBy('min_points')
                ->first();
        }

        // 获取当前段位的积分倍数
        $levelMultiplier = 1.0;
        if ($currentLevel) {
            // 使用反射调用私有方法获取倍数
            $reflection = new \ReflectionClass($this->pointRuleService);
            $method = $reflection->getMethod('getLevelMultiplier');
            $method->setAccessible(true);
            $levelMultiplier = $method->invoke($this->pointRuleService, $memberPoint->level);
        }

        // 获取积分规则信息（用于显示规则介绍）
        $orderRule = $this->pointRuleService->getRule('order_earn');
        $reviewRule = $this->pointRuleService->getRule('review_earn');
        $adoptionRule = $this->pointRuleService->getRule('review_adoption');
        $useRule = $this->pointRuleService->getRule('point_use');
        $expireRule = $this->pointRuleService->getRule('point_expire');

        // 格式化规则信息
        $rulesInfo = [
            'order_earn' => $orderRule ? [
                'name' => $orderRule->rule_name,
                'base_ratio' => $orderRule->config['base_ratio'] ?? 1.0,
                'min_amount' => $orderRule->config['min_amount'] ?? 0,
                'max_points_per_order' => $orderRule->config['max_points_per_order'] ?? null,
            ] : null,
            'review_earn' => $reviewRule ? [
                'name' => $reviewRule->rule_name,
                'base_points' => $reviewRule->config['base_points'] ?? 50,
                'with_image_bonus' => $reviewRule->config['with_image_bonus'] ?? 20,
                'first_review_bonus' => $reviewRule->config['first_review_bonus'] ?? 30,
            ] : null,
            'review_adoption' => $adoptionRule ? [
                'name' => $adoptionRule->rule_name,
                'base_points' => $adoptionRule->config['base_points'] ?? 200,
            ] : null,
            'point_use' => $useRule ? [
                'name' => $useRule->rule_name,
                'use_ratio' => $useRule->config['use_ratio'] ?? 100,
                'min_points' => $useRule->config['min_points'] ?? 1000,
                'max_percent' => $useRule->config['max_percent'] ?? 50,
            ] : null,
            'point_expire' => $expireRule ? [
                'name' => $expireRule->rule_name,
                'expire_days' => $expireRule->config['expire_days'] ?? 365,
            ] : null,
        ];

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'total_points' => $memberPoint->total_points,
                'available_points' => $memberPoint->available_points,
                'frozen_points' => $memberPoint->frozen_points,
                'level' => $memberPoint->level,
                'level_text' => $currentLevel ? $currentLevel->name : $memberPoint->level,
                'level_info' => $currentLevel ? [
                    'id' => $currentLevel->id,
                    'name' => $currentLevel->name,
                    'code' => $currentLevel->code,
                    'icon' => $currentLevel->icon,
                    'color' => $currentLevel->color,
                    'description' => $currentLevel->description,
                    'discount_type' => $currentLevel->discount_type,
                    'discount_value' => $currentLevel->discount_value,
                    'max_discount_amount' => $currentLevel->max_discount_amount,
                    'min_order_amount' => $currentLevel->min_order_amount,
                    'multiplier' => $levelMultiplier,
                ] : null,
                'next_level_info' => $nextLevel ? [
                    'name' => $nextLevel->name,
                    'code' => $nextLevel->code,
                    'min_points' => $nextLevel->min_points,
                ] : null,
                'points_to_next_level' => $pointsToNextLevel,
                'expiring_points' => $expiringPoints,
                'total_expiring' => $totalExpiring,
                'rules_info' => $rulesInfo,
            ],
        ]);
    }

    public function redeem(Request $request): JsonResponse
    {
        $request->validate([
            'reward_id' => 'required|integer|exists:coupons,id',
            'idempotency_key' => 'required|string|max:64',
        ]);

        try {
            $user = Auth::user();
            $result = $this->pointService->redeemCoupon(
                $user,
                $request->input('reward_id'),
                $request->input('idempotency_key')
            );

            return response()->json([
                'code' => 200,
                'message' => '兑换成功',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            $code = $e->getCode() ?: 500;
            return response()->json([
                'code' => $code,
                'message' => $e->getMessage(),
            ], $code >= 400 && $code < 600 ? $code : 500);
        }
    }

    /**
     * 计算到下一级段位所需的积分
     * 
     * 重要：基于总积分（total_points）计算，而不是可用积分（available_points）
     * 段位判断和升级都基于总积分，即使积分被兑换也不影响段位
     * 
     * @param string $currentLevel 当前段位代码
     * @param int $totalPoints 用户的总积分（累计获得的所有积分）
     * @return int 到下一级段位还需要的积分
     */
    private function calculatePointsToNextLevel(string $currentLevel, int $totalPoints): int
    {
        // 使用动态段位配置
        $levels = \App\Models\PointLevel::getActiveLevels();
        
        // 找到当前段位
        $currentLevelObj = $levels->firstWhere('code', $currentLevel);
        if (!$currentLevelObj) {
            return 0;
        }

        // 找到下一个段位（积分要求更高的段位）
        $nextLevel = $levels
            ->where('min_points', '>', $currentLevelObj->min_points)
            ->sortBy('min_points')
            ->first();

        if (!$nextLevel) {
            return 0; // 已经是最高段位
        }

        // 基于总积分计算到下一级还需要的积分
        return max(0, $nextLevel->min_points - $totalPoints);
    }

    /**
     * 获取所有启用的段位列表（前台用户可查看）
     */
    public function getLevels(): JsonResponse
    {
        $levels = PointLevel::where('is_active', true)
            ->orderBy('sort_order', 'asc')
            ->orderBy('min_points', 'asc')
            ->get();

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'levels' => $levels->map(function ($level) {
                    return [
                        'id' => $level->id,
                        'name' => $level->name,
                        'code' => $level->code,
                        'min_points' => $level->min_points,
                        'icon' => $level->icon,
                        'color' => $level->color,
                        'description' => $level->description,
                        'discount_type' => $level->discount_type,
                        'discount_value' => $level->discount_value,
                        'max_discount_amount' => $level->max_discount_amount,
                        'min_order_amount' => $level->min_order_amount,
                    ];
                }),
            ],
        ]);
    }
}

