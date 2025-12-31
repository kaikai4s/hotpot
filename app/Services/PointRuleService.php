<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Services;

use App\Models\MemberPoint;
use App\Models\PointRule;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class PointRuleService
{
    /**
     * 获取规则配置
     */
    public function getRule(string $key): ?PointRule
    {
        return Cache::remember("point_rule:{$key}", 3600, function () use ($key) {
            return PointRule::where('rule_key', $key)
                ->where('is_active', true)
                ->first();
        });
    }

    /**
     * 清除规则缓存
     */
    public function clearCache(string $key): void
    {
        Cache::forget("point_rule:{$key}");
    }

    /**
     * 计算订单应得积分
     */
    public function calculatePointsFromOrder(User $user, float $orderAmount): int
    {
        $rule = $this->getRule('order_earn');
        if (!$rule) {
            // 默认规则：1元 = 1积分
            return (int) floor($orderAmount);
        }

        $config = $rule->config;
        $baseRatio = $config['base_ratio'] ?? 1.0;
        $minAmount = $config['min_amount'] ?? 0;
        $maxPointsPerOrder = $config['max_points_per_order'] ?? null;

        // 检查最低消费金额
        if ($orderAmount < $minAmount) {
            return 0;
        }

        // 获取用户等级倍数
        $memberPoint = MemberPoint::where('user_id', $user->id)->first();
        $level = $memberPoint?->level ?? 'heitie';
        
        // 使用统一的段位倍数获取方法
        $levelMultiplier = $this->getLevelMultiplier($level);

        // 计算积分
        $points = (int) floor($orderAmount * $baseRatio * $levelMultiplier);

        // 检查单次订单最大积分限制
        if ($maxPointsPerOrder !== null && $points > $maxPointsPerOrder) {
            $points = $maxPointsPerOrder;
        }

        return max(0, $points);
    }

    /**
     * 计算评价应得积分（应用段位倍数）
     */
    public function calculatePointsFromReview(User $user, array $reviewData): int
    {
        $rule = $this->getRule('review_earn');
        if (!$rule) {
            // 默认规则：基础50积分
            $points = 50;
            if (!empty($reviewData['images'])) {
                $points += 20; // 带图额外20积分
            }
        } else {
            $config = $rule->config;
            $basePoints = $config['base_points'] ?? 50;
            $withImageBonus = $config['with_image_bonus'] ?? 20;
            $firstReviewBonus = $config['first_review_bonus'] ?? 30;

            $points = $basePoints;

            // 带图片评价额外积分
            if (!empty($reviewData['images']) && count($reviewData['images']) > 0) {
                $points += $withImageBonus;
            }

            // 首次评价额外积分
            if ($reviewData['is_first_review'] ?? false) {
                $points += $firstReviewBonus;
            }
        }

        // 应用段位倍数
        $memberPoint = MemberPoint::where('user_id', $user->id)->first();
        $level = $memberPoint?->level ?? 'heitie';
        
        // 获取段位倍数
        $levelMultiplier = $this->getLevelMultiplier($level);
        
        // 应用倍数
        $points = (int) floor($points * $levelMultiplier);

        return max(0, $points);
    }

    /**
     * 验证积分使用是否符合规则
     */
    public function validateUsePoints(User $user, int $points, float $orderAmount = 0): array
    {
        $rule = $this->getRule('point_use');
        if (!$rule) {
            // 默认规则
            return [
                'valid' => true,
                'use_ratio' => 100, // 100积分=1元
                'min_points' => 1000,
                'max_percent' => 50,
            ];
        }

        $config = $rule->config;
        $useRatio = $config['use_ratio'] ?? 100; // 100积分=1元
        $minPoints = $config['min_points'] ?? 1000;
        $maxPercent = $config['max_percent'] ?? 50; // 最多抵扣50%

        $errors = [];

        // 检查最低使用门槛
        if ($points < $minPoints) {
            $errors[] = "最低使用{$minPoints}积分";
        }

        // 如果用于订单抵扣，检查最大抵扣比例
        if ($orderAmount > 0) {
            $maxDeductAmount = $orderAmount * ($maxPercent / 100);
            $maxPoints = (int) floor($maxDeductAmount * $useRatio);
            if ($points > $maxPoints) {
                $errors[] = "单次订单最多抵扣{$maxPercent}%，最多使用{$maxPoints}积分";
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'use_ratio' => $useRatio,
            'min_points' => $minPoints,
            'max_percent' => $maxPercent,
        ];
    }

    /**
     * 获取积分有效期（天数）
     */
    public function getExpireDays(): int
    {
        $rule = $this->getRule('point_expire');
        if (!$rule) {
            return 365; // 默认365天
        }

        return $rule->config['expire_days'] ?? 365;
    }

    /**
     * 计算评价被采纳应得积分（应用段位倍数）
     */
    public function calculatePointsFromAdoption(User $user): int
    {
        $rule = $this->getRule('review_adoption');
        if (!$rule) {
            // 默认规则：采纳奖励200积分
            $basePoints = 200;
        } else {
            $config = $rule->config;
            $basePoints = $config['base_points'] ?? 200;
        }

        // 应用段位倍数
        $memberPoint = MemberPoint::where('user_id', $user->id)->first();
        $level = $memberPoint?->level ?? 'heitie';
        
        // 获取段位倍数
        $levelMultiplier = $this->getLevelMultiplier($level);
        
        // 应用倍数
        $points = (int) floor($basePoints * $levelMultiplier);

        return max(0, $points);
    }

    /**
     * 获取段位积分倍数
     */
    private function getLevelMultiplier(string $level): float
    {
        // 获取订单积分规则中的段位倍数配置
        $rule = $this->getRule('order_earn');
        if ($rule && isset($rule->config['level_multiplier'])) {
            $multipliers = $rule->config['level_multiplier'];
            
            // 直接查找段位代码
            if (isset($multipliers[$level])) {
                return (float) $multipliers[$level];
            }
        }
        
        // 根据段位代码前缀判断倍数
        if (str_starts_with($level, 'baiyin')) {
            return 1.2; // 白银段位
        } elseif (str_starts_with($level, 'huangjin')) {
            return 1.5; // 黄金段位
        } elseif (str_starts_with($level, 'baijin') || 
                   str_starts_with($level, 'zuanshi') || 
                   str_starts_with($level, 'chaofan') || 
                   str_starts_with($level, 'shenhua') || 
                   str_starts_with($level, 'funeng')) {
            return 2.0; // 白金及以上段位
        }
        
        return 1.0; // 默认（黑铁、青铜等）
    }
}

