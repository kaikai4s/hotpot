<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Observers;

use App\Helpers\LoggerHelper;
use App\Models\Order;
use App\Models\PointTransaction;
use App\Models\UserInvitation;
use App\Services\DepositService;
use App\Services\InvitationService;
use App\Services\PointService;
use App\Services\TaskService;
use Illuminate\Support\Facades\Log;

class OrderObserver
{
    public function __construct(
        private PointService $pointService,
        private DepositService $depositService,
        private InvitationService $invitationService,
        private TaskService $taskService
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
                        LoggerHelper::orderInfo('订单支付积分奖励', [
                            'order_id' => $order->id,
                            'order_no' => $order->order_no,
                            'user_id' => $order->user_id,
                            'status' => $order->status,
                            'points' => $transaction->points,
                        ]);
                        LoggerHelper::pointInfo('订单支付获得积分', [
                            'order_id' => $order->id,
                            'order_no' => $order->order_no,
                            'user_id' => $order->user_id,
                            'points' => $transaction->points,
                        ]);
                        Log::info('订单支付积分奖励', [
                            'order_id' => $order->id,
                            'order_no' => $order->order_no,
                            'user_id' => $order->user_id,
                            'status' => $order->status,
                            'points' => $transaction->points,
                        ]);
                    }
                } else {
                    LoggerHelper::orderDebug('订单积分已发放，跳过重复发放', [
                        'order_id' => $order->id,
                        'order_no' => $order->order_no,
                    ]);
                    Log::info('订单积分已发放，跳过重复发放', [
                        'order_id' => $order->id,
                        'order_no' => $order->order_no,
                    ]);
                }
            } catch (\Exception $e) {
                LoggerHelper::orderError('订单支付积分奖励失败', [
                    'order_id' => $order->id,
                    'order_no' => $order->order_no,
                    'user_id' => $order->user_id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                LoggerHelper::pointError('订单支付积分奖励失败', [
                    'order_id' => $order->id,
                    'order_no' => $order->order_no,
                    'user_id' => $order->user_id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
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
                            LoggerHelper::orderInfo('订单完成，预约定金已自动返还', [
                                'order_id' => $order->id,
                                'order_no' => $order->order_no,
                                'reservation_id' => $reservation->id,
                                'reservation_code' => $reservation->reservation_code,
                                'deposit_amount' => $reservation->deposit_amount,
                            ]);
                            LoggerHelper::depositInfo('订单完成，预约定金已自动返还', [
                                'order_id' => $order->id,
                                'order_no' => $order->order_no,
                                'reservation_id' => $reservation->id,
                                'reservation_code' => $reservation->reservation_code,
                                'deposit_amount' => $reservation->deposit_amount,
                            ]);
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
                LoggerHelper::orderError('订单完成时返还定金失败', [
                    'order_id' => $order->id,
                    'order_no' => $order->order_no,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                LoggerHelper::depositError('订单完成时返还定金失败', [
                    'order_id' => $order->id,
                    'order_no' => $order->order_no,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                Log::error('订单完成时返还定金失败', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        // 订单支付后，检查是否是首次消费，如果是则发放邀请奖励
        if ($isPaidStatus && $hasPaidAt && ($statusChanged || $paidAtChanged)) {
            try {
                $user = $order->user;
                if ($user && $user->invited_by) {
                    // 检查是否是首次消费
                    $isFirstOrder = Order::where('user_id', $user->id)
                        ->whereIn('status', ['paid', 'pending_review', 'completed'])
                        ->count() === 1;

                    if ($isFirstOrder) {
                        // 查找邀请记录
                        $invitation = UserInvitation::where('invitee_id', $user->id)
                            ->where('status', 'registered')
                            ->first();

                        if ($invitation && !$invitation->reward_issued) {
                            // 更新邀请记录的首次消费时间
                            $invitation->update([
                                'first_order_at' => now(),
                            ]);

                            // 发放邀请奖励
                            $this->invitationService->issueInvitationRewards($invitation);

                            LoggerHelper::orderInfo('首次消费，邀请奖励已发放', [
                                'order_id' => $order->id,
                                'user_id' => $user->id,
                                'invitation_id' => $invitation->id,
                                'inviter_id' => $invitation->inviter_id,
                            ]);
                            LoggerHelper::userInfo('首次消费，邀请奖励已发放', [
                                'order_id' => $order->id,
                                'user_id' => $user->id,
                                'invitation_id' => $invitation->id,
                                'inviter_id' => $invitation->inviter_id,
                            ]);
                            Log::info('首次消费，邀请奖励已发放', [
                                'order_id' => $order->id,
                                'user_id' => $user->id,
                                'invitation_id' => $invitation->id,
                                'inviter_id' => $invitation->inviter_id,
                            ]);
                        }
                    }
                }
            } catch (\Exception $e) {
                    LoggerHelper::orderError('首次消费邀请奖励发放失败', [
                    'order_id' => $order->id,
                    'user_id' => $order->user_id,
                    'error' => $e->getMessage(),
                ]);
                    Log::error('首次消费邀请奖励发放失败', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }

            // 检测任务完成（订单相关任务）
            try {
                // 传递订单金额用于成就任务检测
                $this->taskService->checkTaskCompletion(
                    $order->user,
                    'order',
                    1,
                    ['amount' => (float) $order->total_amount]
                );
            } catch (\Exception $e) {
                LoggerHelper::orderError('订单任务完成检测失败', [
                    'order_id' => $order->id,
                    'user_id' => $order->user_id,
                    'error' => $e->getMessage(),
                ]);
                Log::error('订单任务完成检测失败', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }

            // 检测成就完成（消费类成就）
            try {
                $achievementService = app(\App\Services\AchievementService::class);
                $achievementService->checkAchievementCompletion(
                    $order->user,
                    'consume',
                    1,
                    ['amount' => (float) $order->total_amount]
                );
            } catch (\Exception $e) {
                LoggerHelper::orderError('订单成就完成检测失败', [
                    'order_id' => $order->id,
                    'user_id' => $order->user_id,
                    'error' => $e->getMessage(),
                ]);
                Log::error('订单成就完成检测失败', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // 订单取消时，如果有使用积分抵扣，需要解冻积分
        if ($order->status === 'cancelled' && $order->wasChanged('status')) {
            // TODO: 如果订单使用了积分抵扣，需要解冻积分
            // 这个功能需要订单表增加积分使用相关字段

            // 释放桌位（如果订单关联了桌位）
            try {
                if ($order->table_id) {
                    // 加载桌位关联
                    $order->load('table');
                    $table = $order->table;
                    if ($table && $table->status === 'occupied') {
                        // 检查该桌位是否还有其他进行中的订单
                        $hasActiveOrders = \App\Models\Order::where('table_id', $table->id)
                            ->where('id', '!=', $order->id)
                            ->whereIn('status', ['pending', 'paid', 'pending_review'])
                            ->exists();

                        // 如果没有其他进行中的订单，释放桌位
                        if (!$hasActiveOrders) {
                            $table->update([
                                'status' => 'available',
                                'occupied_at' => null,
                            ]);

                            LoggerHelper::orderInfo('订单取消，桌位已释放', [
                                'order_id' => $order->id,
                                'order_no' => $order->order_no,
                                'table_id' => $table->id,
                                'table_name' => $table->name,
                            ]);
                            LoggerHelper::tableInfo('订单取消，桌位已释放', [
                                'order_id' => $order->id,
                                'order_no' => $order->order_no,
                                'table_id' => $table->id,
                                'table_name' => $table->name,
                            ]);
                            Log::info('订单取消，桌位已释放', [
                                'order_id' => $order->id,
                                'order_no' => $order->order_no,
                                'table_id' => $table->id,
                                'table_name' => $table->name,
                            ]);
                        }
                    }
                }
            } catch (\Exception $e) {
                LoggerHelper::orderError('订单取消时释放桌位失败', [
                    'order_id' => $order->id,
                    'order_no' => $order->order_no,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                LoggerHelper::tableError('订单取消时释放桌位失败', [
                    'order_id' => $order->id,
                    'order_no' => $order->order_no,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                Log::error('订单取消时释放桌位失败', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // 订单完成时，释放桌位
        if ($order->status === 'completed' && $order->wasChanged('status')) {
            try {
                if ($order->table_id) {
                    // 加载桌位关联
                    $order->load('table');
                    $table = $order->table;
                    if ($table && $table->status === 'occupied') {
                        // 检查该桌位是否还有其他进行中的订单
                        $hasActiveOrders = \App\Models\Order::where('table_id', $table->id)
                            ->where('id', '!=', $order->id)
                            ->whereIn('status', ['pending', 'paid', 'pending_review'])
                            ->exists();

                        // 如果没有其他进行中的订单，释放桌位
                        if (!$hasActiveOrders) {
                            $table->update([
                                'status' => 'available',
                                'occupied_at' => null,
                                'occupied_by_user_id' => null,
                                'team_code' => null,
                            ]);

                            LoggerHelper::orderInfo('订单完成，桌位已释放', [
                                'order_id' => $order->id,
                                'order_no' => $order->order_no,
                                'table_id' => $table->id,
                                'table_name' => $table->name,
                            ]);
                            LoggerHelper::tableInfo('订单完成，桌位已释放', [
                                'order_id' => $order->id,
                                'order_no' => $order->order_no,
                                'table_id' => $table->id,
                                'table_name' => $table->name,
                            ]);
                            Log::info('订单完成，桌位已释放', [
                                'order_id' => $order->id,
                                'order_no' => $order->order_no,
                                'table_id' => $table->id,
                                'table_name' => $table->name,
                            ]);
                        }
                    }
                }
            } catch (\Exception $e) {
                LoggerHelper::orderError('订单完成时释放桌位失败', [
                    'order_id' => $order->id,
                    'order_no' => $order->order_no,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                LoggerHelper::tableError('订单完成时释放桌位失败', [
                    'order_id' => $order->id,
                    'order_no' => $order->order_no,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                Log::error('订单完成时释放桌位失败', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}

