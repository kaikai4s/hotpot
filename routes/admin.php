<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

use App\Http\Controllers\Api\Admin\V1\AdminController;
use App\Http\Controllers\Api\Admin\V1\AuthController;
use App\Http\Controllers\Api\Admin\V1\AuditLogController;
use App\Http\Controllers\Api\Admin\V1\BannerController;
use App\Http\Controllers\Api\Admin\V1\ComboController;
use App\Http\Controllers\Api\Admin\V1\ConfigController;
use App\Http\Controllers\Api\Admin\V1\CouponController;
use App\Http\Controllers\Api\Admin\V1\DashboardController;
use App\Http\Controllers\Api\Admin\V1\DishController;
use App\Http\Controllers\Api\Admin\V1\LotteryController;
use App\Http\Controllers\Api\Admin\V1\OrderController;
use App\Http\Controllers\Api\Admin\V1\PermissionController;
use App\Http\Controllers\Api\Admin\V1\PointsController;
use App\Http\Controllers\Api\Admin\V1\PointLevelController;
use App\Http\Controllers\Api\Admin\V1\PointRuleController;
use App\Http\Controllers\Api\Admin\V1\PointStatisticsController;
use App\Http\Controllers\Api\Admin\V1\DepositController;
use App\Http\Controllers\Api\Admin\V1\ReservationController;
use App\Http\Controllers\Api\Admin\V1\ReviewController;
use App\Http\Controllers\Api\Admin\V1\RestaurantAreaController;
use App\Http\Controllers\Api\Admin\V1\RoleController;
use App\Http\Controllers\Api\Admin\V1\TableController;
use App\Http\Controllers\Api\Admin\V1\UploadController;
use App\Http\Controllers\Api\Admin\V1\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin/v1')->group(function () {
    // 认证相关（无需登录）
    Route::post('/auth/login', [AuthController::class, 'login']);

        // 需要认证的路由（包含日志记录中间件）
        Route::middleware(['auth.admin', \App\Http\Middleware\LogAdminActivity::class])->group(function () {
        // 认证相关
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        // 仪表盘统计
        Route::get('/dashboard/statistics', [DashboardController::class, 'statistics']);

        // 权限管理
        Route::get('/permissions', [PermissionController::class, 'index'])->middleware('permission:roles.view');

        // 角色管理
        Route::get('/roles', [RoleController::class, 'index'])->middleware('permission:roles.view');
        Route::post('/roles', [RoleController::class, 'store'])->middleware('permission:roles.create');
        Route::put('/roles/{id}', [RoleController::class, 'update'])->middleware('permission:roles.update');
        Route::delete('/roles/{id}', [RoleController::class, 'destroy'])->middleware('permission:roles.delete');

        // 预约管理
        Route::get('/reservations', [ReservationController::class, 'index'])->middleware('permission:reservations.view');
        Route::get('/reservations/{id}', [ReservationController::class, 'show'])->middleware('permission:reservations.view');
        Route::post('/reservations/{id}/confirm', [ReservationController::class, 'confirm'])->middleware('permission:reservations.update');
        Route::post('/reservations/{id}/cancel', [ReservationController::class, 'cancel'])->middleware('permission:reservations.update');

        // 定金管理
        Route::get('/deposits', [DepositController::class, 'index'])->middleware('permission:reservations.view');
        Route::post('/deposits/{reservationId}/refund', [DepositController::class, 'refund'])->middleware('permission:reservations.update');

        // 评价管理
        Route::get('/reviews', [ReviewController::class, 'index'])->middleware('permission:reviews.view');
        Route::get('/reviews/{reviewId}', [ReviewController::class, 'show'])->middleware('permission:reviews.view');
        Route::put('/reviews/{reviewId}/approve', [ReviewController::class, 'approve'])->middleware('permission:reviews.approve');
        Route::post('/reviews/{reviewId}/reply', [ReviewController::class, 'reply'])->middleware('permission:reviews.reply');
        Route::post('/reviews/{reviewId}/adopt', [ReviewController::class, 'adopt'])->middleware('permission:reviews.adopt');
        Route::put('/reviews/{reviewId}/tracking', [ReviewController::class, 'updateTracking'])->middleware('permission:reviews.track');
        Route::post('/reviews/{reviewId}/tracking-update', [ReviewController::class, 'addTrackingUpdate'])->middleware('permission:reviews.track');

        // 桌位管理
        Route::get('/tables', [TableController::class, 'index'])->middleware('permission:tables.view');
        Route::post('/tables', [TableController::class, 'store'])->middleware('permission:tables.create');
        Route::get('/tables/{id}', [TableController::class, 'show'])->middleware('permission:tables.view');
        Route::put('/tables/{id}', [TableController::class, 'update'])->middleware('permission:tables.update');
        Route::delete('/tables/{id}', [TableController::class, 'destroy'])->middleware('permission:tables.delete');
        Route::post('/tables/positions', [TableController::class, 'updatePositions'])->middleware('permission:tables.position');

        // 菜品管理
        Route::get('/dishes', [DishController::class, 'index'])->middleware('permission:tables.view');
        Route::get('/dishes/categories', [DishController::class, 'categories'])->middleware('permission:tables.view');
        Route::get('/dishes/{id}', [DishController::class, 'show'])->middleware('permission:tables.view');
        Route::post('/dishes', [DishController::class, 'store'])->middleware('permission:tables.create');
        Route::put('/dishes/{id}', [DishController::class, 'update'])->middleware('permission:tables.update');
        Route::delete('/dishes/{id}', [DishController::class, 'destroy'])->middleware('permission:tables.delete');

        // 套餐管理
        Route::get('/combos', [ComboController::class, 'index'])->middleware('permission:tables.view');
        Route::get('/combos/{id}', [ComboController::class, 'show'])->middleware('permission:tables.view');
        Route::post('/combos', [ComboController::class, 'store'])->middleware('permission:tables.create');
        Route::put('/combos/{id}', [ComboController::class, 'update'])->middleware('permission:tables.update');
        Route::delete('/combos/{id}', [ComboController::class, 'destroy'])->middleware('permission:tables.delete');

        // 区域管理
        Route::get('/areas', [RestaurantAreaController::class, 'index'])->middleware('permission:areas.view');
        Route::post('/areas', [RestaurantAreaController::class, 'store'])->middleware('permission:areas.create');
        Route::put('/areas/{id}', [RestaurantAreaController::class, 'update'])->middleware('permission:areas.update');
        Route::post('/areas/batch-update', [RestaurantAreaController::class, 'batchUpdate'])->middleware('permission:areas.update');
        Route::delete('/areas/{id}', [RestaurantAreaController::class, 'destroy'])->middleware('permission:areas.delete');

        // 用户管理
        Route::get('/users', [UserController::class, 'index'])->middleware('permission:users.view');
        Route::get('/users/statistics', [UserController::class, 'statistics'])->middleware('permission:users.view');
        Route::get('/users/{id}', [UserController::class, 'show'])->middleware('permission:users.view');
        Route::put('/users/{id}', [UserController::class, 'update'])->middleware('permission:users.update');
        Route::delete('/users/{id}', [UserController::class, 'destroy'])->middleware('permission:users.delete');
        Route::post('/users/batch-destroy', [UserController::class, 'batchDestroy'])->middleware('permission:users.delete');

        // 配置管理
        Route::get('/configs', [ConfigController::class, 'index'])->middleware('permission:configs.view');
        Route::get('/configs/{key}', [ConfigController::class, 'show'])->middleware('permission:configs.view');
        Route::put('/configs/{id}', [ConfigController::class, 'update'])->middleware('permission:configs.update');
        Route::post('/configs/batch-update', [ConfigController::class, 'batchUpdate'])->middleware('permission:configs.update');

        // 文件上传
        Route::post('/upload/image', [UploadController::class, 'uploadImage'])->middleware('permission:configs.update');
        Route::delete('/upload/image', [UploadController::class, 'deleteImage'])->middleware('permission:configs.update');

        // 轮播图管理
        Route::get('/banners', [BannerController::class, 'index'])->middleware('permission:configs.view');
        Route::get('/banners/{id}', [BannerController::class, 'show'])->middleware('permission:configs.view');
        Route::post('/banners', [BannerController::class, 'store'])->middleware('permission:configs.update');
        Route::put('/banners/{id}', [BannerController::class, 'update'])->middleware('permission:configs.update');
        Route::delete('/banners/{id}', [BannerController::class, 'destroy'])->middleware('permission:configs.update');
        Route::post('/banners/update-order', [BannerController::class, 'updateOrder'])->middleware('permission:configs.update');

        // 管理员管理
        Route::get('/admins', [AdminController::class, 'index'])->middleware('permission:admins.view');
        Route::get('/admins/{id}', [AdminController::class, 'show'])->middleware('permission:admins.view');
        Route::post('/admins', [AdminController::class, 'store'])->middleware('permission:admins.create');
        Route::put('/admins/{id}', [AdminController::class, 'update'])->middleware('permission:admins.update');
        Route::delete('/admins/{id}', [AdminController::class, 'destroy'])->middleware('permission:admins.delete');

        // 积分系统管理
        Route::prefix('points')->group(function () {
            Route::get('/', [PointsController::class, 'index'])->middleware('permission:points.view');
            Route::get('/{userId}', [PointsController::class, 'show'])->middleware('permission:points.view');
            Route::get('/{userId}/transactions', [PointsController::class, 'transactions'])->middleware('permission:points.view');
            Route::post('/{userId}/adjust', [PointsController::class, 'adjust'])->middleware('permission:points.update');
        });

        // 积分规则管理
        Route::prefix('point-rules')->group(function () {
            Route::get('/', [PointRuleController::class, 'index'])->middleware('permission:points.view');
            Route::get('/{id}', [PointRuleController::class, 'show'])->middleware('permission:points.view');
            Route::post('/', [PointRuleController::class, 'store'])->middleware('permission:points.update');
            Route::put('/{id}', [PointRuleController::class, 'update'])->middleware('permission:points.update');
            Route::delete('/{id}', [PointRuleController::class, 'destroy'])->middleware('permission:points.update');
        });

        // 积分段位管理
        Route::prefix('point-levels')->group(function () {
            Route::get('/active', [PointLevelController::class, 'getActiveLevels'])->middleware('permission:points.view');
            Route::get('/used-icons', [PointLevelController::class, 'getUsedIcons'])->middleware('permission:points.view');
            Route::post('/update-all-levels', [PointLevelController::class, 'updateAllUserLevels'])->middleware('permission:points.update');
            Route::get('/', [PointLevelController::class, 'index'])->middleware('permission:points.view');
            Route::get('/{id}', [PointLevelController::class, 'show'])->middleware('permission:points.view');
            Route::post('/', [PointLevelController::class, 'store'])->middleware('permission:points.update');
            Route::put('/{id}', [PointLevelController::class, 'update'])->middleware('permission:points.update');
            Route::delete('/{id}', [PointLevelController::class, 'destroy'])->middleware('permission:points.update');
            Route::post('/{id}/toggle-active', [PointLevelController::class, 'toggleActive'])->middleware('permission:points.update');
        });

        // 积分统计
        Route::prefix('point-statistics')->group(function () {
            Route::get('/report', [PointStatisticsController::class, 'report'])->middleware('permission:points.view');
            Route::get('/ranking', [PointStatisticsController::class, 'ranking'])->middleware('permission:points.view');
            Route::get('/source-statistics', [PointStatisticsController::class, 'sourceStatistics'])->middleware('permission:points.view');
        });

        // 积分异常检测
        Route::prefix('point-anomalies')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\Admin\V1\PointAnomalyController::class, 'index'])->middleware('permission:points.view');
            Route::get('/summary', [\App\Http\Controllers\Api\Admin\V1\PointAnomalyController::class, 'summary'])->middleware('permission:points.view');
        });

        // 优惠活动管理
        Route::get('/coupons', [CouponController::class, 'index'])->middleware('permission:coupons.view');
        Route::get('/coupons/{id}', [CouponController::class, 'show'])->middleware('permission:coupons.view');
        Route::post('/coupons', [CouponController::class, 'store'])->middleware('permission:coupons.create');
        Route::put('/coupons/{id}', [CouponController::class, 'update'])->middleware('permission:coupons.update');
        Route::delete('/coupons/{id}', [CouponController::class, 'destroy'])->middleware('permission:coupons.delete');
        Route::get('/coupons/{id}/usage', [CouponController::class, 'usage'])->middleware('permission:coupons.view');

        // 订单管理
        Route::get('/orders', [OrderController::class, 'index'])->middleware('permission:orders.view');
        Route::get('/orders/{id}', [OrderController::class, 'show'])->middleware('permission:orders.view');
        Route::put('/orders/{id}/status', [OrderController::class, 'updateStatus'])->middleware('permission:orders.update');
        Route::post('/orders/{id}/cancel', [OrderController::class, 'cancel'])->middleware('permission:orders.update');
        Route::post('/orders/{id}/complete', [OrderController::class, 'complete'])->middleware('permission:orders.update');

        // 操作日志管理
        Route::get('/audit-logs', [AuditLogController::class, 'index'])->middleware('permission:audit_logs.view');
        Route::get('/audit-logs/{id}', [AuditLogController::class, 'show'])->middleware('permission:audit_logs.view');
        Route::get('/audit-logs/statistics', [AuditLogController::class, 'statistics'])->middleware('permission:audit_logs.view');

        // 抽奖活动管理
        Route::get('/lottery/activities', [LotteryController::class, 'index'])->middleware('permission:coupons.view');
        Route::get('/lottery/activities/{id}', [LotteryController::class, 'show'])->middleware('permission:coupons.view');
        Route::post('/lottery/activities', [LotteryController::class, 'store'])->middleware('permission:coupons.create');
        Route::put('/lottery/activities/{id}', [LotteryController::class, 'update'])->middleware('permission:coupons.update');
        Route::delete('/lottery/activities/{id}', [LotteryController::class, 'destroy'])->middleware('permission:coupons.delete');
        
        // 奖品管理
        Route::get('/lottery/activities/{activityId}/prizes', [LotteryController::class, 'prizes'])->middleware('permission:coupons.view');
        Route::post('/lottery/activities/{activityId}/prizes', [LotteryController::class, 'storePrize'])->middleware('permission:coupons.create');
        Route::put('/lottery/activities/{activityId}/prizes/{prizeId}', [LotteryController::class, 'updatePrize'])->middleware('permission:coupons.update');
        Route::delete('/lottery/activities/{activityId}/prizes/{prizeId}', [LotteryController::class, 'destroyPrize'])->middleware('permission:coupons.delete');
        
        // 抽奖记录
        Route::get('/lottery/activities/{activityId}/records', [LotteryController::class, 'records'])->middleware('permission:coupons.view');
    });
});

