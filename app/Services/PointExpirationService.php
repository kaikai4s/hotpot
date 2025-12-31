<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Services;

use App\Models\MemberPoint;
use App\Models\PointExpiration;
use App\Models\PointTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PointExpirationService
{
    /**
     * 安排积分过期时间
     */
    public function scheduleExpiration(PointTransaction $transaction, int $validDays): void
    {
        // 只有获得类型的积分才需要安排过期
        if ($transaction->type !== 'earn' || $transaction->points <= 0) {
            return;
        }

        $expireAt = $transaction->created_at->copy()->addDays($validDays);

        PointExpiration::create([
            'user_id' => $transaction->user_id,
            'transaction_id' => $transaction->id,
            'points' => $transaction->points,
            'expire_at' => $expireAt,
            'status' => 'pending',
        ]);

        // 更新交易记录的过期时间
        $transaction->update(['expire_at' => $expireAt]);
    }

    /**
     * 处理过期积分
     */
    public function processExpirations(): int
    {
        $processed = 0;
        $now = now();

        // 查找所有已过期但未处理的记录
        $expirations = PointExpiration::where('status', 'pending')
            ->where('expire_at', '<=', $now)
            ->with(['user', 'transaction'])
            ->get();

        foreach ($expirations as $expiration) {
            try {
                DB::transaction(function () use ($expiration, $now, &$processed) {
                    $user = $expiration->user;
                    $memberPoint = MemberPoint::lockForUpdate()
                        ->where('user_id', $user->id)
                        ->first();

                    if (!$memberPoint) {
                        return;
                    }

                    // 检查可用积分是否足够
                    if ($memberPoint->available_points < $expiration->points) {
                        // 如果可用积分不足，只扣除可用积分
                        $expiration->points = $memberPoint->available_points;
                    }

                    if ($expiration->points <= 0) {
                        // 标记为已取消（积分已被使用）
                        $expiration->update([
                            'status' => 'cancelled',
                            'expired_at' => $now,
                        ]);
                        return;
                    }

                    // 扣除积分
                    $memberPoint->available_points = max(0, $memberPoint->available_points - $expiration->points);
                    $memberPoint->save();

                    // 记录过期交易
                    PointTransaction::create([
                        'user_id' => $user->id,
                        'type' => 'expire',
                        'points' => -$expiration->points,
                        'balance_after' => $memberPoint->available_points,
                        'source_type' => 'expiration',
                        'source_id' => $expiration->id,
                        'description' => '积分过期',
                    ]);

                    // 更新过期记录状态
                    $expiration->update([
                        'status' => 'expired',
                        'expired_at' => $now,
                    ]);

                    $processed++;
                });
            } catch (\Exception $e) {
                Log::error('处理积分过期失败', [
                    'expiration_id' => $expiration->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $processed;
    }

    /**
     * 取消过期安排（当积分被使用时）
     */
    public function cancelExpiration(PointTransaction $transaction): void
    {
        PointExpiration::where('transaction_id', $transaction->id)
            ->where('status', 'pending')
            ->update([
                'status' => 'cancelled',
                'expired_at' => now(),
            ]);
    }

    /**
     * 获取用户即将过期的积分
     */
    public function getExpiringPoints(User $user, int $days = 30): array
    {
        $expirations = PointExpiration::where('user_id', $user->id)
            ->where('status', 'pending')
            ->whereBetween('expire_at', [now(), now()->addDays($days)])
            ->orderBy('expire_at', 'asc')
            ->get();

        $result = [];
        foreach ($expirations as $expiration) {
            $result[] = [
                'points' => $expiration->points,
                'expire_at' => $expiration->expire_at->format('Y-m-d'),
                'days_left' => now()->diffInDays($expiration->expire_at, false),
            ];
        }

        return $result;
    }

    /**
     * 获取用户即将过期的积分总数
     */
    public function getExpiringPointsTotal(User $user, int $days = 30): int
    {
        return PointExpiration::where('user_id', $user->id)
            ->where('status', 'pending')
            ->whereBetween('expire_at', [now(), now()->addDays($days)])
            ->sum('points');
    }
}

