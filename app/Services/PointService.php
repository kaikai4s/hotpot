<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Services;

use App\Models\MemberPoint;
use App\Models\PointTransaction;
use App\Models\User;
use App\Models\Coupon;
use App\Models\UserCoupon;
use App\Models\Order;
use App\Models\Review;
use App\Services\PointRuleService;
use App\Services\PointExpirationService;
use Illuminate\Support\Facades\DB;

class PointService
{
    public function __construct(
        private PointRuleService $ruleService,
        private PointExpirationService $expirationService
    ) {
    }
    public function getPoints(User $user): MemberPoint
    {
        // 获取默认段位（最低积分的段位）
        $defaultLevel = \App\Models\PointLevel::getActiveLevels()
            ->sortBy('min_points')
            ->first();
        $defaultLevelCode = $defaultLevel ? $defaultLevel->code : 'bronze';

        $memberPoint = MemberPoint::firstOrCreate(
            ['user_id' => $user->id],
            [
                'total_points' => 0,
                'available_points' => 0,
                'frozen_points' => 0,
                'level' => $defaultLevelCode,
            ]
        );

        // 检查并更新已存在用户的段位（确保段位与当前积分匹配）
        $this->updateLevel($memberPoint);

        return $memberPoint;
    }

    public function earnPoints(User $user, int $points, string $sourceType, ?int $sourceId = null, ?string $description = null, ?int $expireDays = null): PointTransaction
    {
        return DB::transaction(function () use ($user, $points, $sourceType, $sourceId, $description, $expireDays) {
            // 获取默认段位（最低积分的段位）
            $defaultLevel = \App\Models\PointLevel::getActiveLevels()
                ->sortBy('min_points')
                ->first();
            $defaultLevelCode = $defaultLevel ? $defaultLevel->code : 'bronze';

            $memberPoint = MemberPoint::lockForUpdate()->firstOrCreate(
                ['user_id' => $user->id],
                [
                    'total_points' => 0,
                    'available_points' => 0,
                    'frozen_points' => 0,
                    'level' => $defaultLevelCode,
                ]
            );

            $memberPoint->total_points += $points;
            $memberPoint->available_points += $points;
            $memberPoint->save();

            // 检查等级
            $this->updateLevel($memberPoint);

            // 记录流水
            $transaction = PointTransaction::create([
                'user_id' => $user->id,
                'type' => 'earn',
                'points' => $points,
                'balance_after' => $memberPoint->available_points,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'description' => $description,
            ]);

            // 安排过期时间
            if ($expireDays === null) {
                $expireDays = $this->ruleService->getExpireDays();
            }
            if ($expireDays > 0) {
                $this->expirationService->scheduleExpiration($transaction, $expireDays);
            }

            return $transaction;
        });
    }

    /**
     * 订单支付获得积分
     */
    public function earnPointsFromOrder(Order $order): ?PointTransaction
    {
        $user = $order->user;
        if (!$user) {
            return null;
        }

        // 检查是否已经为该订单发放过积分（避免重复发放）
        $existingTransaction = PointTransaction::where('source_type', 'order')
            ->where('source_id', $order->id)
            ->where('type', 'earn')
            ->first();

        if ($existingTransaction) {
            return null; // 已经发放过积分，不再重复发放
        }

        // 计算应得积分
        $points = $this->ruleService->calculatePointsFromOrder($user, (float) $order->total_amount);
        
        if ($points <= 0) {
            return null;
        }

        return $this->earnPoints(
            $user,
            $points,
            'order',
            $order->id,
            "订单支付获得积分（订单号：{$order->order_no}）"
        );
    }

    /**
     * 评价获得积分
     */
    public function earnPointsFromReview(Review $review): ?PointTransaction
    {
        $user = $review->user;
        if (!$user) {
            return null;
        }

        // 检查是否已经为该评价发放过积分（避免重复发放）
        $existingTransaction = PointTransaction::where('source_type', 'review')
            ->where('source_id', $review->id)
            ->where('type', 'earn')
            ->first();

        if ($existingTransaction) {
            return null; // 已经发放过积分，不再重复发放
        }

        // 检查是否首次评价（检查是否有其他已审核通过的评价）
        $isFirstReview = Review::where('user_id', $user->id)
            ->where('status', 'approved')
            ->where('id', '!=', $review->id)
            ->doesntExist();

        // 计算应得积分
        $points = $this->ruleService->calculatePointsFromReview($user, [
            'images' => $review->images ?? [],
            'is_first_review' => $isFirstReview,
        ]);

        if ($points <= 0) {
            return null;
        }

        return $this->earnPoints(
            $user,
            $points,
            'review',
            $review->id,
            $isFirstReview ? '首次评价奖励' : '评价奖励'
        );
    }

    /**
     * 评价被采纳获得积分
     */
    public function earnPointsFromAdoption(Review $review): ?PointTransaction
    {
        $user = $review->user;
        if (!$user) {
            return null;
        }

        // 检查是否已经为该评价发放过采纳积分（避免重复发放）
        $existingTransaction = PointTransaction::where('source_type', 'review_adoption')
            ->where('source_id', $review->id)
            ->where('type', 'earn')
            ->first();

        if ($existingTransaction) {
            return null; // 已经发放过采纳积分，不再重复发放
        }

        // 计算应得积分（应用段位倍数）
        $points = $this->ruleService->calculatePointsFromAdoption($user);
        
        if ($points <= 0) {
            return null;
        }

        return $this->earnPoints(
            $user,
            $points,
            'review_adoption',
            $review->id,
            "评价建议被采纳奖励（评价ID：{$review->id}）"
        );
    }

    public function redeemCoupon(User $user, int $couponId, string $idempotencyKey): array
    {
        // 检查幂等性
        $existing = PointTransaction::where('source_type', 'redeem')
            ->where('source_id', $couponId)
            ->where('description', $idempotencyKey)
            ->first();

        if ($existing) {
            throw new \Exception('重复兑换', 409);
        }

        $coupon = Coupon::findOrFail($couponId);

        if (!$coupon->is_active) {
            throw new \Exception('优惠券不可用', 400);
        }

        if ($coupon->stock <= 0) {
            throw new \Exception('库存不足', 400);
        }

        return DB::transaction(function () use ($user, $coupon, $idempotencyKey) {
            // 获取默认段位（最低积分的段位）
            $defaultLevel = \App\Models\PointLevel::getActiveLevels()
                ->sortBy('min_points')
                ->first();
            $defaultLevelCode = $defaultLevel ? $defaultLevel->code : 'bronze';

            $memberPoint = MemberPoint::lockForUpdate()->firstOrCreate(
                ['user_id' => $user->id],
                [
                    'total_points' => 0,
                    'available_points' => 0,
                    'frozen_points' => 0,
                    'level' => $defaultLevelCode,
                ]
            );

            if ($memberPoint->available_points < $coupon->points_required) {
                throw new \Exception('积分不足', 400);
            }

            // 扣除积分
            $memberPoint->available_points -= $coupon->points_required;
            $memberPoint->frozen_points += $coupon->points_required;
            $memberPoint->save();

            // 减少库存
            $coupon->decrement('stock');

            // 记录流水
            PointTransaction::create([
                'user_id' => $user->id,
                'type' => 'redeem',
                'points' => -$coupon->points_required,
                'balance_after' => $memberPoint->available_points,
                'source_type' => 'redeem',
                'source_id' => $coupon->id,
                'description' => $idempotencyKey,
            ]);

            // 生成用户优惠券
            $userCoupon = $user->userCoupons()->create([
                'coupon_id' => $coupon->id,
                'code' => 'CP' . strtoupper(uniqid()),
                'status' => 'unused',
                'expires_at' => $coupon->valid_to,
            ]);

            return [
                'coupon_id' => $userCoupon->id,
                'points_used' => $coupon->points_required,
                'remaining_points' => $memberPoint->available_points,
            ];
        });
    }

    /**
     * 解冻积分（优惠券使用或过期）
     */
    public function unfreezePoints(UserCoupon $userCoupon, string $reason = 'used'): void
    {
        DB::transaction(function () use ($userCoupon, $reason) {
            $user = $userCoupon->user;
            $coupon = $userCoupon->coupon;
            
            $memberPoint = MemberPoint::lockForUpdate()
                ->where('user_id', $user->id)
                ->first();

            if (!$memberPoint || $memberPoint->frozen_points < $coupon->points_required) {
                return;
            }

            // 解冻积分
            $memberPoint->frozen_points -= $coupon->points_required;
            
            if ($reason === 'used') {
                // 优惠券已使用，积分不再返还
                // 只解冻，不增加可用积分
            } else {
                // 优惠券过期，积分返还
                $memberPoint->available_points += $coupon->points_required;
            }
            
            $memberPoint->save();

            // 记录流水
            if ($reason === 'expired') {
                PointTransaction::create([
                    'user_id' => $user->id,
                    'type' => 'adjust',
                    'points' => $coupon->points_required,
                    'balance_after' => $memberPoint->available_points,
                    'source_type' => 'coupon_expired',
                    'source_id' => $userCoupon->id,
                    'description' => '优惠券过期，积分返还',
                ]);
            }
        });
    }

    /**
     * 获取即将过期的积分
     */
    public function getExpiringPoints(User $user, int $days = 30): array
    {
        return $this->expirationService->getExpiringPoints($user, $days);
    }

    /**
     * 检查并处理过期积分（定时任务调用）
     */
    public function checkAndExpirePoints(): int
    {
        return $this->expirationService->processExpirations();
    }

    /**
     * 更新用户段位
     * 
     * 重要：段位判断基于 total_points（总积分），而不是 available_points（可用积分）
     * 总积分是用户累计获得的所有积分，不会因为积分兑换而减少
     * 可用积分是当前可以使用的积分，会因兑换、过期等原因减少
     * 
     * @param MemberPoint $memberPoint 用户积分记录
     */
    private function updateLevel(MemberPoint $memberPoint): void
    {
        // 使用总积分（total_points）来判断段位，而不是可用积分（available_points）
        // 这样即使积分被兑换或过期，段位也不会降低
        $totalPoints = $memberPoint->total_points;
        
        // 使用动态段位配置
        $newLevel = \App\Models\PointLevel::getLevelByPoints($totalPoints);
        $newLevelCode = $newLevel ? $newLevel->code : 'bronze';

        if ($memberPoint->level !== $newLevelCode) {
            $memberPoint->update(['level' => $newLevelCode]);
        }
    }
}

