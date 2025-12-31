<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Providers;

use App\Models\Order;
use App\Models\Review;
use App\Models\UserCoupon;
use App\Observers\OrderObserver;
use App\Observers\ReviewObserver;
use App\Observers\UserCouponObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // 注册观察者
        Order::observe(OrderObserver::class);
        Review::observe(ReviewObserver::class);
        UserCoupon::observe(UserCouponObserver::class);
    }
}

