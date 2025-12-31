<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Observers;

use App\Models\Order;
use App\Models\PointTransaction;
use App\Services\DepositService;
use App\Services\PointService;
use Illuminate\Support\Facades\Log;

class OrderObserver
{
    public function __construct(
        private PointService $pointService,
        private DepositService $depositService
    ) {
    }

    /**
     * 订单状态变更时触发积分获得
     * 当订单状态变为 paid 或 pending_review 时发放积分（支付后立即获得积分）
     * 注意：pending_review 状态也会触发积分发放，因为支付可能直接进入该状态
     */
    public function updated(Order $order): void
    {
        // 检查订单状态是否从非 paid/pending_review 变为 paid 或 pending_review（支付后立即获得积分）
        // 同时检查 paid_at 字段是否已设置，确保是支付操作而非其他状态变更
        $isPaidStatus = in_array($order->status, ['paid', 'pending_review']);
        $hasPaidAt = $order->paid_at !== null;
        $statusChanged = $order->wasChanged('status');
        $paidAtChanged = $order->wasChanged('paid_at') && $order->paid_at !== null;

        if ($isPaidStatus && $hasPaidAt && ($statusChanged || $paidAtChanged)) {
            try {
                // 检查是否已经为该订单发放过积分（避免重复发放）
                $existingTransaction = \App\Models\PointTransaction::where('source_type', 'order')
                    ->where('source_id', $order->id)
                    ->where('type', 'earn')
                    ->first();

                if (!$existingTransaction) {
                    $transaction = $this->pointService->earnPointsFromOrder($order);
                    if ($transaction) {
                        Log::info('订单支付积分奖励', [
                            'order_id' => $order->id,
                            'order_no' => $order->order_no,
                            'user_id' => $order->user_id,
                            'status' => $order->status,
                            'points' => $transaction->points,
                        ]);
                    }
                } else {
                    Log::info('订单积分已发放，跳过重复发放', [
                        'order_id' => $order->id,
                        'order_no' => $order->order_no,
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('订单支付积分奖励失败', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        // 订单完成时，如果关联了预约且预约已到达，自动返还定金
        if ($order->status === 'completed' && $order->wasChanged('status')) {
            try {
                // 加载预约关联
                $order->load('reservation');
                
                if ($order->reservation) {
                    $reservation = $order->reservation;
                    
                    // 如果预约已到达且定金已支付但未返还，则原路返还
                    if ($reservation->arrived_at 
                        && $reservation->deposit_status === 'paid' 
                        && !$reservation->deposit_refunded_at) {
                        
                        $refunded = $this->depositService->refundDeposit($reservation);
                        
                        if ($refunded) {
                            Log::info('订单完成，预约定金已自动返还', [
                                'order_id' => $order->id,
                                'order_no' => $order->order_no,
                                'reservation_id' => $reservation->id,
                                'reservation_code' => $reservation->reservation_code,
                                'deposit_amount' => $reservation->deposit_amount,
                            ]);
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::error('订单完成时返还定金失败', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        // 订单取消时，如果有使用积分抵扣，需要解冻积分
        if ($order->status === 'cancelled' && $order->wasChanged('status')) {
            // TODO: 如果订单使用了积分抵扣，需要解冻积分
            // 这个功能需要订单表增加积分使用相关字段
        }
    }
}

