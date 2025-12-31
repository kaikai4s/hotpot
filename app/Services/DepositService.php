<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Services;

use App\Models\Reservation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DepositService
{
    /**
     * 原路返还定金
     * 当订单完成后，如果预约已到达且定金已支付但未返还，则原路返还
     */
    public function refundDeposit(Reservation $reservation): bool
    {
        // 检查预约状态
        if ($reservation->deposit_status !== 'paid') {
            Log::warning('预约定金未支付，无法返还', [
                'reservation_id' => $reservation->id,
                'deposit_status' => $reservation->deposit_status,
            ]);
            return false;
        }

        // 检查是否已经返还
        if ($reservation->deposit_refunded_at) {
            Log::info('预约定金已返还，跳过重复返还', [
                'reservation_id' => $reservation->id,
            ]);
            return false;
        }

        // 检查预约是否已到达
        if (!$reservation->arrived_at) {
            Log::warning('预约未到达，无法返还定金', [
                'reservation_id' => $reservation->id,
            ]);
            return false;
        }

        try {
            DB::transaction(function () use ($reservation) {
                // 更新预约状态为已返还
                $reservation->update([
                    'deposit_status' => 'refunded',
                    'deposit_refunded_at' => now(),
                ]);

                // 这里可以调用实际的支付接口进行原路返还
                // 目前是模拟返还，实际应该根据 deposit_data 中的支付方式调用相应的退款接口
                // 例如：微信支付退款、支付宝退款等
                
                Log::info('预约定金已原路返还', [
                    'reservation_id' => $reservation->id,
                    'reservation_code' => $reservation->reservation_code,
                    'deposit_amount' => $reservation->deposit_amount,
                    'deposit_transaction_id' => $reservation->deposit_transaction_id,
                    'payment_method' => $reservation->deposit_data['method'] ?? 'unknown',
                ]);
            });

            return true;
        } catch (\Exception $e) {
            Log::error('返还预约定金失败', [
                'reservation_id' => $reservation->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    /**
     * 手动返还定金（后台管理员操作）
     */
    public function manualRefundDeposit(int $reservationId, ?string $reason = null): bool
    {
        $reservation = Reservation::findOrFail($reservationId);

        if ($reservation->deposit_status !== 'paid') {
            throw new \Exception('预约定金未支付，无法返还', 400);
        }

        if ($reservation->deposit_refunded_at) {
            throw new \Exception('预约定金已返还', 400);
        }

        try {
            DB::transaction(function () use ($reservation, $reason) {
                $reservation->update([
                    'deposit_status' => 'refunded',
                    'deposit_refunded_at' => now(),
                ]);

                Log::info('管理员手动返还预约定金', [
                    'reservation_id' => $reservation->id,
                    'reservation_code' => $reservation->reservation_code,
                    'deposit_amount' => $reservation->deposit_amount,
                    'reason' => $reason,
                ]);
            });

            return true;
        } catch (\Exception $e) {
            Log::error('手动返还预约定金失败', [
                'reservation_id' => $reservationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}


