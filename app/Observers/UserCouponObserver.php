<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Observers;

use App\Models\UserCoupon;
use App\Services\PointService;
use Illuminate\Support\Facades\Log;

class UserCouponObserver
{
    public function __construct(
        private PointService $pointService
    ) {
    }

    /**
     * 优惠券状态变更时处理积分解冻
     */
    public function updated(UserCoupon $userCoupon): void
    {
        // 优惠券被使用
        if ($userCoupon->status === 'used' && $userCoupon->wasChanged('status')) {
            try {
                $this->pointService->unfreezePoints($userCoupon, 'used');
                Log::info('优惠券使用，积分解冻（已使用）', [
                    'user_coupon_id' => $userCoupon->id,
                    'user_id' => $userCoupon->user_id,
                ]);
            } catch (\Exception $e) {
                Log::error('优惠券使用积分解冻失败', [
                    'user_coupon_id' => $userCoupon->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // 优惠券过期
        if ($userCoupon->status === 'expired' && $userCoupon->wasChanged('status')) {
            try {
                $this->pointService->unfreezePoints($userCoupon, 'expired');
                Log::info('优惠券过期，积分解冻（返还）', [
                    'user_coupon_id' => $userCoupon->id,
                    'user_id' => $userCoupon->user_id,
                ]);
            } catch (\Exception $e) {
                Log::error('优惠券过期积分解冻失败', [
                    'user_coupon_id' => $userCoupon->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}

