<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BannerController;
use App\Http\Controllers\Api\V1\ComboController;
use App\Http\Controllers\Api\V1\CouponController;
use App\Http\Controllers\Api\V1\DishController;
use App\Http\Controllers\Api\V1\PointController;
use App\Http\Controllers\Api\V1\PointTransactionController;
use App\Http\Controllers\Api\V1\QueueController;
use App\Http\Controllers\Api\V1\ReservationController;
use App\Http\Controllers\Api\V1\ReviewController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // 公开接口
    Route::post('/auth/wechat-login', [AuthController::class, 'wechatLogin']);
    Route::get('/wechat/config', [\App\Http\Controllers\Api\V1\WechatConfigController::class, 'getConfig']);
    
    // 公开配置接口（前台可访问）
    Route::get('/configs/{key}', [\App\Http\Controllers\Api\V1\ConfigController::class, 'getPublicConfig']);
    
    // 轮播图接口（公开接口）
    Route::get('/banners', [BannerController::class, 'index']);
    
    // 菜品相关（公开接口）
    Route::get('/dishes', [DishController::class, 'index']);
    Route::get('/dishes/categories', [DishController::class, 'categories']);
    Route::get('/dishes/{id}', [DishController::class, 'show']);
    Route::get('/dishes/{dishId}/reviews', [ReviewController::class, 'getDishReviews']); // 菜品评价（公开接口）
    
    // 套餐相关（公开接口）
    Route::get('/combos', [ComboController::class, 'index']);
    Route::get('/combos/{id}', [ComboController::class, 'show']);
    
    // 预约相关 - 获取可用桌位（公开接口，预约前需要查看）
    Route::get('/reservations/tables', [ReservationController::class, 'getAvailableTables']);

    // 需要认证的接口
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/users/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        // 预约相关
        Route::get('/reservations', [ReservationController::class, 'index']);
        Route::get('/reservations/{reservationId}', [ReservationController::class, 'show']);
        Route::post('/reservations', [ReservationController::class, 'create']);
        Route::post('/reservations/{reservationId}/pay-deposit', [ReservationController::class, 'payDeposit']);
        Route::post('/reservations/{reservationId}/arrive', [ReservationController::class, 'arrive']);
        Route::put('/reservations/{reservationId}/confirm', [ReservationController::class, 'confirm']);
        Route::put('/reservations/{reservationId}/cancel', [ReservationController::class, 'cancel']);

        // 评价相关
        Route::post('/reviews', [ReviewController::class, 'create']);
        Route::get('/reviews', [ReviewController::class, 'index']); // 获取所有评价（包括追踪优化的）
        Route::get('/reviews/tracking', [ReviewController::class, 'getTrackingReviews']); // 获取追踪优化的评价

        // 排队相关
        Route::post('/queue/join', [QueueController::class, 'join']);
        Route::get('/queue/{queueId}', [QueueController::class, 'getStatus']);

        // 积分相关
        Route::get('/points', [PointController::class, 'getPoints']);
        Route::get('/points/levels', [PointController::class, 'getLevels']); // 获取所有启用的段位列表
        Route::get('/points/transactions', [PointTransactionController::class, 'index']);
        Route::get('/points/expiring', [PointTransactionController::class, 'expiring']);
        Route::post('/points/redeem', [PointController::class, 'redeem']);

        // 优惠券相关（前端用户）
        Route::get('/coupons/available', [CouponController::class, 'getAvailableCoupons']);
        Route::get('/coupons/all', [CouponController::class, 'getAllAvailableCoupons']);
        Route::post('/coupons/claim', [CouponController::class, 'claimCoupon']);
        Route::get('/coupons/my', [CouponController::class, 'getUserCoupons']);

        // 抽奖相关
        Route::get('/lottery/activities', [\App\Http\Controllers\Api\V1\LotteryController::class, 'activities']);
        Route::get('/lottery/activities/{id}', [\App\Http\Controllers\Api\V1\LotteryController::class, 'showActivity']);
        Route::post('/lottery/activities/{id}/draw', [\App\Http\Controllers\Api\V1\LotteryController::class, 'draw']);
        Route::get('/lottery/records', [\App\Http\Controllers\Api\V1\LotteryController::class, 'myRecords']);

        // 订单相关
        Route::get('/orders/payment-methods', [\App\Http\Controllers\Api\V1\OrderController::class, 'getPaymentMethods']);
        Route::post('/orders', [\App\Http\Controllers\Api\V1\OrderController::class, 'create']);
        Route::get('/orders', [\App\Http\Controllers\Api\V1\OrderController::class, 'index']);
            Route::get('/orders/{orderId}', [\App\Http\Controllers\Api\V1\OrderController::class, 'show']);
            Route::put('/orders/{orderId}', [\App\Http\Controllers\Api\V1\OrderController::class, 'update']);
            Route::post('/orders/{orderId}/pay', [\App\Http\Controllers\Api\V1\OrderController::class, 'pay']);
            Route::post('/orders/{orderId}/skip-review', [\App\Http\Controllers\Api\V1\OrderController::class, 'skipReview']);

        // 文件上传（前端用户）
        Route::post('/upload/image', [\App\Http\Controllers\Api\V1\UploadController::class, 'uploadImage']);
            Route::post('/orders/{orderId}/cancel', [\App\Http\Controllers\Api\V1\OrderController::class, 'cancel']);
    });
});

