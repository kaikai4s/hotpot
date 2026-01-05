<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Helpers;

use Illuminate\Support\Facades\Log;

/**
 * 日志辅助类
 * 提供统一的日志记录接口，自动分类到对应的模块和级别
 */
class LoggerHelper
{
    /**
     * 订单模块日志
     */
    public static function orderDebug(string $message, array $context = []): void
    {
        Log::channel('order_debug')->debug($message, $context);
        Log::channel('order')->debug($message, $context);
    }

    public static function orderInfo(string $message, array $context = []): void
    {
        Log::channel('order_info')->info($message, $context);
        Log::channel('order')->info($message, $context);
    }

    public static function orderWarning(string $message, array $context = []): void
    {
        Log::channel('order_warning')->warning($message, $context);
        Log::channel('order')->warning($message, $context);
    }

    public static function orderError(string $message, array $context = []): void
    {
        Log::channel('order_error')->error($message, $context);
        Log::channel('order')->error($message, $context);
    }

    /**
     * 定金模块日志
     */
    public static function depositDebug(string $message, array $context = []): void
    {
        Log::channel('deposit_debug')->debug($message, $context);
        Log::channel('deposit')->debug($message, $context);
    }

    public static function depositInfo(string $message, array $context = []): void
    {
        Log::channel('deposit_info')->info($message, $context);
        Log::channel('deposit')->info($message, $context);
    }

    public static function depositWarning(string $message, array $context = []): void
    {
        Log::channel('deposit_warning')->warning($message, $context);
        Log::channel('deposit')->warning($message, $context);
    }

    public static function depositError(string $message, array $context = []): void
    {
        Log::channel('deposit_error')->error($message, $context);
        Log::channel('deposit')->error($message, $context);
    }

    /**
     * 用户模块日志
     */
    public static function userDebug(string $message, array $context = []): void
    {
        Log::channel('user_debug')->debug($message, $context);
        Log::channel('user')->debug($message, $context);
    }

    public static function userInfo(string $message, array $context = []): void
    {
        Log::channel('user_info')->info($message, $context);
        Log::channel('user')->info($message, $context);
    }

    public static function userWarning(string $message, array $context = []): void
    {
        Log::channel('user_warning')->warning($message, $context);
        Log::channel('user')->warning($message, $context);
    }

    public static function userError(string $message, array $context = []): void
    {
        Log::channel('user_error')->error($message, $context);
        Log::channel('user')->error($message, $context);
    }

    /**
     * 桌位模块日志
     */
    public static function tableDebug(string $message, array $context = []): void
    {
        Log::channel('table_debug')->debug($message, $context);
        Log::channel('table')->debug($message, $context);
    }

    public static function tableInfo(string $message, array $context = []): void
    {
        Log::channel('table_info')->info($message, $context);
        Log::channel('table')->info($message, $context);
    }

    public static function tableWarning(string $message, array $context = []): void
    {
        Log::channel('table_warning')->warning($message, $context);
        Log::channel('table')->warning($message, $context);
    }

    public static function tableError(string $message, array $context = []): void
    {
        Log::channel('table_error')->error($message, $context);
        Log::channel('table')->error($message, $context);
    }

    /**
     * 积分模块日志
     */
    public static function pointDebug(string $message, array $context = []): void
    {
        Log::channel('point_debug')->debug($message, $context);
        Log::channel('point')->debug($message, $context);
    }

    public static function pointInfo(string $message, array $context = []): void
    {
        Log::channel('point_info')->info($message, $context);
        Log::channel('point')->info($message, $context);
    }

    public static function pointWarning(string $message, array $context = []): void
    {
        Log::channel('point_warning')->warning($message, $context);
        Log::channel('point')->warning($message, $context);
    }

    public static function pointError(string $message, array $context = []): void
    {
        Log::channel('point_error')->error($message, $context);
        Log::channel('point')->error($message, $context);
    }

    /**
     * 预约模块日志
     */
    public static function reservationDebug(string $message, array $context = []): void
    {
        Log::channel('reservation_debug')->debug($message, $context);
        Log::channel('reservation')->debug($message, $context);
    }

    public static function reservationInfo(string $message, array $context = []): void
    {
        Log::channel('reservation_info')->info($message, $context);
        Log::channel('reservation')->info($message, $context);
    }

    public static function reservationWarning(string $message, array $context = []): void
    {
        Log::channel('reservation_warning')->warning($message, $context);
        Log::channel('reservation')->warning($message, $context);
    }

    public static function reservationError(string $message, array $context = []): void
    {
        Log::channel('reservation_error')->error($message, $context);
        Log::channel('reservation')->error($message, $context);
    }

    /**
     * 评价模块日志
     */
    public static function reviewDebug(string $message, array $context = []): void
    {
        Log::channel('review_debug')->debug($message, $context);
        Log::channel('review')->debug($message, $context);
    }

    public static function reviewInfo(string $message, array $context = []): void
    {
        Log::channel('review_info')->info($message, $context);
        Log::channel('review')->info($message, $context);
    }

    public static function reviewWarning(string $message, array $context = []): void
    {
        Log::channel('review_warning')->warning($message, $context);
        Log::channel('review')->warning($message, $context);
    }

    public static function reviewError(string $message, array $context = []): void
    {
        Log::channel('review_error')->error($message, $context);
        Log::channel('review')->error($message, $context);
    }

    /**
     * 支付模块日志
     */
    public static function paymentDebug(string $message, array $context = []): void
    {
        Log::channel('payment_debug')->debug($message, $context);
        Log::channel('payment')->debug($message, $context);
    }

    public static function paymentInfo(string $message, array $context = []): void
    {
        Log::channel('payment_info')->info($message, $context);
        Log::channel('payment')->info($message, $context);
    }

    public static function paymentWarning(string $message, array $context = []): void
    {
        Log::channel('payment_warning')->warning($message, $context);
        Log::channel('payment')->warning($message, $context);
    }

    public static function paymentError(string $message, array $context = []): void
    {
        Log::channel('payment_error')->error($message, $context);
        Log::channel('payment')->error($message, $context);
    }
}

