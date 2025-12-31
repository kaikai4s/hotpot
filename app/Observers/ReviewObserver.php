<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Observers;

use App\Models\Review;
use App\Services\PointService;
use Illuminate\Support\Facades\Log;

class ReviewObserver
{
    public function __construct(
        private PointService $pointService
    ) {
    }

    /**
     * 评价创建时触发积分获得（如果状态为 approved）
     */
    public function created(Review $review): void
    {
        // 如果评价创建时就是 approved 状态，直接发放积分
        if ($review->status === 'approved') {
            try {
                $transaction = $this->pointService->earnPointsFromReview($review);
                if ($transaction) {
                    Log::info('评价创建积分奖励', [
                        'review_id' => $review->id,
                        'user_id' => $review->user_id,
                        'dish_id' => $review->dish_id,
                        'points' => $transaction->points,
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('评价创建积分奖励失败', [
                    'review_id' => $review->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }
    }

    /**
     * 评价状态变更时触发积分获得
     */
    public function updated(Review $review): void
    {
        // 检查评价状态是否从非 approved 变为 approved
        if ($review->status === 'approved' && $review->wasChanged('status')) {
            try {
                $transaction = $this->pointService->earnPointsFromReview($review);
                if ($transaction) {
                    Log::info('评价审核通过积分奖励', [
                        'review_id' => $review->id,
                        'user_id' => $review->user_id,
                        'dish_id' => $review->dish_id,
                        'points' => $transaction->points,
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('评价审核通过积分奖励失败', [
                    'review_id' => $review->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        // 检查评价是否被采纳（is_adopted 从 false 变为 true）
        if ($review->is_adopted && $review->wasChanged('is_adopted')) {
            try {
                $transaction = $this->pointService->earnPointsFromAdoption($review);
                if ($transaction) {
                    Log::info('评价采纳积分奖励', [
                        'review_id' => $review->id,
                        'user_id' => $review->user_id,
                        'points' => $transaction->points,
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('评价采纳积分奖励失败', [
                    'review_id' => $review->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }
    }
}

