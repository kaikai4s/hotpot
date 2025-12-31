<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Services;

use App\Models\LotteryActivity;
use App\Models\LotteryPrize;
use App\Models\LotteryRecord;
use App\Models\User;
use App\Models\UserCoupon;
use App\Models\MemberPoint;
use App\Models\PointTransaction;
use App\Services\PointService;
use Illuminate\Support\Facades\DB;

class LotteryService
{
    public function __construct(
        private PointService $pointService
    ) {
    }

    /**
     * 执行抽奖
     */
    public function draw(User $user, int $activityId): array
    {
        // 只加载激活的奖品
        $activity = LotteryActivity::with(['prizes' => function ($query) {
            $query->where('is_active', true);
        }])->findOrFail($activityId);

        // 检查活动是否可用
        if (!$activity->isActive()) {
            throw new \Exception('活动未开始或已结束', 400);
        }

        // 检查每日抽奖次数限制
        if ($activity->daily_limit > 0) {
            $todayCount = LotteryRecord::where('user_id', $user->id)
                ->where('lottery_activity_id', $activityId)
                ->whereDate('created_at', today())
                ->count();
            
            if ($todayCount >= $activity->daily_limit) {
                throw new \Exception("今日抽奖次数已达上限（{$activity->daily_limit}次）", 400);
            }
        }

        // 检查总抽奖次数限制
        if ($activity->total_limit > 0) {
            $totalCount = LotteryRecord::where('user_id', $user->id)
                ->where('lottery_activity_id', $activityId)
                ->count();
            
            if ($totalCount >= $activity->total_limit) {
                throw new \Exception("总抽奖次数已达上限（{$activity->total_limit}次）", 400);
            }
        }

        // 扣除积分
        if ($activity->points_cost > 0) {
            $memberPoint = $this->pointService->getPoints($user);
            if ($memberPoint->available_points < $activity->points_cost) {
                throw new \Exception('积分不足', 400);
            }

            // 使用消费积分的方法
            $this->consumePoints($user, $activity->points_cost, $activityId, "抽奖活动：{$activity->name}");
        }

        return DB::transaction(function () use ($user, $activity) {
            // 抽奖逻辑
            $prize = $this->drawPrize($activity);

            // 记录抽奖结果
            $record = LotteryRecord::create([
                'user_id' => $user->id,
                'lottery_activity_id' => $activity->id,
                'lottery_prize_id' => $prize ? $prize->id : null,
                'prize_type' => $prize ? $prize->prize_type : 'none',
                'prize_id' => $prize ? $prize->prize_id : null,
                'prize_value' => $prize ? $prize->prize_value : null,
                'is_winner' => $prize !== null,
            ]);

            // 如果中奖，发放奖品
            if ($prize) {
                $this->grantPrize($user, $prize);
            }

            return [
                'record' => $record->load('prize'),
                'prize' => $prize,
                'is_winner' => $prize !== null,
            ];
        });
    }

    /**
     * 抽奖算法
     */
    private function drawPrize(LotteryActivity $activity): ?LotteryPrize
    {
        $prizes = $activity->prizes;
        
        if ($prizes->isEmpty()) {
            return null;
        }

        // 计算总概率
        $totalProbability = $prizes->sum('probability');
        
        // 生成随机数（0-10000）
        $random = mt_rand(1, 10000);

        // 检查库存和激活状态
        $availablePrizes = $prizes->filter(function ($prize) {
            // 检查是否激活
            if (!$prize->is_active) {
                return false;
            }

            // 检查总库存
            if ($prize->stock > 0) {
                $usedCount = LotteryRecord::where('lottery_prize_id', $prize->id)
                    ->where('is_winner', true)
                    ->count();
                
                if ($usedCount >= $prize->stock) {
                    return false;
                }
            }

            // 检查每日库存
            if ($prize->daily_stock > 0) {
                $todayUsedCount = LotteryRecord::where('lottery_prize_id', $prize->id)
                    ->where('is_winner', true)
                    ->whereDate('created_at', today())
                    ->count();
                
                if ($todayUsedCount >= $prize->daily_stock) {
                    return false;
                }
            }

            return true;
        });

        if ($availablePrizes->isEmpty()) {
            return null; // 所有奖品都无库存
        }

        // 重新计算可用奖品的总概率
        $availableTotalProbability = $availablePrizes->sum('probability');
        
        // 如果随机数大于总概率，未中奖
        if ($random > $availableTotalProbability) {
            return null;
        }

        // 按概率分配
        $currentProbability = 0;
        foreach ($availablePrizes as $prize) {
            $currentProbability += $prize->probability;
            if ($random <= $currentProbability) {
                return $prize;
            }
        }

        return null;
    }

    /**
     * 发放奖品
     */
    private function grantPrize(User $user, LotteryPrize $prize): void
    {
        switch ($prize->prize_type) {
            case 'coupon':
                // 发放优惠券
                $coupon = \App\Models\Coupon::findOrFail($prize->prize_id);
                
                if (!$coupon->isUsable()) {
                    throw new \Exception('优惠券不可用', 400);
                }

                // 减少库存
                $coupon->decrement('stock');

                // 生成优惠券码
                $code = 'CP' . strtoupper(uniqid());
                
                // 创建用户优惠券
                UserCoupon::create([
                    'user_id' => $user->id,
                    'coupon_id' => $coupon->id,
                    'code' => $code,
                    'status' => 'unused',
                    'obtained_from' => 'lottery',
                    'obtained_at' => now(),
                    'expires_at' => $coupon->valid_to,
                ]);
                break;

            case 'points':
                // 发放积分
                $this->pointService->earnPoints(
                    $user,
                    $prize->prize_value,
                    'lottery',
                    $prize->id,
                    "抽奖获得：{$prize->name}"
                );
                break;

            case 'dish':
                // 发放菜品兑换券（创建特殊优惠券）
                $dish = \App\Models\Dish::findOrFail($prize->prize_id);
                
                // 创建一个临时的菜品兑换优惠券
                $coupon = \App\Models\Coupon::create([
                    'name' => "{$dish->name}兑换券",
                    'type' => 'dish_exchange',
                    'dish_id' => $dish->id,
                    'value' => $dish->price,
                    'min_amount' => 0,
                    'points_required' => 0,
                    'stock' => 1,
                    'valid_from' => now(),
                    'valid_to' => now()->addDays(30), // 30天有效期
                    'is_active' => true,
                    'description' => "通过抽奖获得的{$dish->name}兑换券",
                ]);

                // 生成优惠券码
                $code = 'CP' . strtoupper(uniqid());
                
                // 创建用户优惠券
                UserCoupon::create([
                    'user_id' => $user->id,
                    'coupon_id' => $coupon->id,
                    'code' => $code,
                    'status' => 'unused',
                    'obtained_from' => 'lottery',
                    'obtained_at' => now(),
                    'expires_at' => $coupon->valid_to,
                ]);
                break;
        }
    }

    /**
     * 获取用户今日抽奖次数
     */
    public function getTodayDrawCount(User $user, int $activityId): int
    {
        return LotteryRecord::where('user_id', $user->id)
            ->where('lottery_activity_id', $activityId)
            ->whereDate('created_at', today())
            ->count();
    }

    /**
     * 获取用户总抽奖次数
     */
    public function getTotalDrawCount(User $user, int $activityId): int
    {
        return LotteryRecord::where('user_id', $user->id)
            ->where('lottery_activity_id', $activityId)
            ->count();
    }

    /**
     * 消费积分（用于抽奖）
     */
    private function consumePoints(User $user, int $points, int $sourceId, string $description): void
    {
        DB::transaction(function () use ($user, $points, $sourceId, $description) {
            $memberPoint = MemberPoint::lockForUpdate()->firstOrCreate(
                ['user_id' => $user->id],
                [
                    'total_points' => 0,
                    'available_points' => 0,
                    'frozen_points' => 0,
                    'level' => 'bronze',
                ]
            );

            if ($memberPoint->available_points < $points) {
                throw new \Exception('积分不足', 400);
            }

            // 扣除积分
            $memberPoint->available_points -= $points;
            $memberPoint->save();

            // 记录流水
            PointTransaction::create([
                'user_id' => $user->id,
                'type' => 'consume',
                'points' => -$points,
                'balance_after' => $memberPoint->available_points,
                'source_type' => 'lottery',
                'source_id' => $sourceId,
                'description' => $description,
            ]);
        });
    }
}

