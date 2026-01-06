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
     * 敏感字段列表，这些字段的值会被过滤
     */
    private static array $sensitiveFields = [
        'password',
        'password_confirmation',
        'token',
        'api_token',
        'access_token',
        'refresh_token',
        'openid',
        'unionid',
        'payment_data',
        'deposit_data',
        'payment_transaction_id',
        'deposit_transaction_id',
        'card_number',
        'cvv',
        'cvc',
        'secret',
        'api_key',
        'private_key',
    ];

    /**
     * 过滤敏感信息
     */
    private static function sanitizeContext(array $context): array
    {
        $sanitized = [];
        foreach ($context as $key => $value) {
            // 确保 key 是字符串类型，防止类型错误
            $keyLower = is_string($key) ? strtolower($key) : strtolower((string) $key);
            if (in_array($keyLower, self::$sensitiveFields, true)) {
                $sanitized[$key] = '***FILTERED***';
            } elseif (is_array($value)) {
                $sanitized[$key] = self::sanitizeContext($value);
            } elseif (is_object($value)) {
                // 对象转换为数组后过滤
                $arrayValue = json_decode(json_encode($value), true);
                if (is_array($arrayValue)) {
                    $sanitized[$key] = self::sanitizeContext($arrayValue);
                } else {
                    $sanitized[$key] = $value;
                }
            } else {
                $sanitized[$key] = $value;
            }
        }
        return $sanitized;
    }
    /**
     * 订单模块日志
     */
    public static function orderDebug(string $message, array $context = []): void
    {
        $sanitized = self::sanitizeContext($context);
        Log::channel('order_debug')->debug($message, $sanitized);
        Log::channel('order')->debug($message, $sanitized);
    }

    public static function orderInfo(string $message, array $context = []): void
    {
        $sanitized = self::sanitizeContext($context);
        Log::channel('order_info')->info($message, $sanitized);
        Log::channel('order')->info($message, $sanitized);
    }

    public static function orderWarning(string $message, array $context = []): void
    {
        $sanitized = self::sanitizeContext($context);
        Log::channel('order_warning')->warning($message, $sanitized);
        Log::channel('order')->warning($message, $sanitized);
    }

    public static function orderError(string $message, array $context = []): void
    {
        $sanitized = self::sanitizeContext($context);
        Log::channel('order_error')->error($message, $sanitized);
        Log::channel('order')->error($message, $sanitized);
    }

    /**
     * 定金模块日志
     */
    public static function depositDebug(string $message, array $context = []): void
    {
        $sanitized = self::sanitizeContext($context);
        Log::channel('deposit_debug')->debug($message, $sanitized);
        Log::channel('deposit')->debug($message, $sanitized);
    }

    public static function depositInfo(string $message, array $context = []): void
    {
        $sanitized = self::sanitizeContext($context);
        Log::channel('deposit_info')->info($message, $sanitized);
        Log::channel('deposit')->info($message, $sanitized);
    }

    public static function depositWarning(string $message, array $context = []): void
    {
        $sanitized = self::sanitizeContext($context);
        Log::channel('deposit_warning')->warning($message, $sanitized);
        Log::channel('deposit')->warning($message, $sanitized);
    }

    public static function depositError(string $message, array $context = []): void
    {
        $sanitized = self::sanitizeContext($context);
        Log::channel('deposit_error')->error($message, $sanitized);
        Log::channel('deposit')->error($message, $sanitized);
    }

    /**
     * 用户模块日志
     */
    public static function userDebug(string $message, array $context = []): void
    {
        $sanitized = self::sanitizeContext($context);
        Log::channel('user_debug')->debug($message, $sanitized);
        Log::channel('user')->debug($message, $sanitized);
    }

    public static function userInfo(string $message, array $context = []): void
    {
        $sanitized = self::sanitizeContext($context);
        Log::channel('user_info')->info($message, $sanitized);
        Log::channel('user')->info($message, $sanitized);
    }

    public static function userWarning(string $message, array $context = []): void
    {
        $sanitized = self::sanitizeContext($context);
        Log::channel('user_warning')->warning($message, $sanitized);
        Log::channel('user')->warning($message, $sanitized);
    }

    public static function userError(string $message, array $context = []): void
    {
        $sanitized = self::sanitizeContext($context);
        Log::channel('user_error')->error($message, $sanitized);
        Log::channel('user')->error($message, $sanitized);
    }

    /**
     * 桌位模块日志
     */
    public static function tableDebug(string $message, array $context = []): void
    {
        $sanitized = self::sanitizeContext($context);
        Log::channel('table_debug')->debug($message, $sanitized);
        Log::channel('table')->debug($message, $sanitized);
    }

    public static function tableInfo(string $message, array $context = []): void
    {
        $sanitized = self::sanitizeContext($context);
        Log::channel('table_info')->info($message, $sanitized);
        Log::channel('table')->info($message, $sanitized);
    }

    public static function tableWarning(string $message, array $context = []): void
    {
        $sanitized = self::sanitizeContext($context);
        Log::channel('table_warning')->warning($message, $sanitized);
        Log::channel('table')->warning($message, $sanitized);
    }

    public static function tableError(string $message, array $context = []): void
    {
        $sanitized = self::sanitizeContext($context);
        Log::channel('table_error')->error($message, $sanitized);
        Log::channel('table')->error($message, $sanitized);
    }

    /**
     * 积分模块日志
     */
    public static function pointDebug(string $message, array $context = []): void
    {
        $sanitized = self::sanitizeContext($context);
        Log::channel('point_debug')->debug($message, $sanitized);
        Log::channel('point')->debug($message, $sanitized);
    }

    public static function pointInfo(string $message, array $context = []): void
    {
        $sanitized = self::sanitizeContext($context);
        Log::channel('point_info')->info($message, $sanitized);
        Log::channel('point')->info($message, $sanitized);
    }

    public static function pointWarning(string $message, array $context = []): void
    {
        $sanitized = self::sanitizeContext($context);
        Log::channel('point_warning')->warning($message, $sanitized);
        Log::channel('point')->warning($message, $sanitized);
    }

    public static function pointError(string $message, array $context = []): void
    {
        $sanitized = self::sanitizeContext($context);
        Log::channel('point_error')->error($message, $sanitized);
        Log::channel('point')->error($message, $sanitized);
    }

    /**
     * 预约模块日志
     */
    public static function reservationDebug(string $message, array $context = []): void
    {
        $sanitized = self::sanitizeContext($context);
        Log::channel('reservation_debug')->debug($message, $sanitized);
        Log::channel('reservation')->debug($message, $sanitized);
    }

    public static function reservationInfo(string $message, array $context = []): void
    {
        $sanitized = self::sanitizeContext($context);
        Log::channel('reservation_info')->info($message, $sanitized);
        Log::channel('reservation')->info($message, $sanitized);
    }

    public static function reservationWarning(string $message, array $context = []): void
    {
        $sanitized = self::sanitizeContext($context);
        Log::channel('reservation_warning')->warning($message, $sanitized);
        Log::channel('reservation')->warning($message, $sanitized);
    }

    public static function reservationError(string $message, array $context = []): void
    {
        $sanitized = self::sanitizeContext($context);
        Log::channel('reservation_error')->error($message, $sanitized);
        Log::channel('reservation')->error($message, $sanitized);
    }

    /**
     * 评价模块日志
     */
    public static function reviewDebug(string $message, array $context = []): void
    {
        $sanitized = self::sanitizeContext($context);
        Log::channel('review_debug')->debug($message, $sanitized);
        Log::channel('review')->debug($message, $sanitized);
    }

    public static function reviewInfo(string $message, array $context = []): void
    {
        $sanitized = self::sanitizeContext($context);
        Log::channel('review_info')->info($message, $sanitized);
        Log::channel('review')->info($message, $sanitized);
    }

    public static function reviewWarning(string $message, array $context = []): void
    {
        $sanitized = self::sanitizeContext($context);
        Log::channel('review_warning')->warning($message, $sanitized);
        Log::channel('review')->warning($message, $sanitized);
    }

    public static function reviewError(string $message, array $context = []): void
    {
        $sanitized = self::sanitizeContext($context);
        Log::channel('review_error')->error($message, $sanitized);
        Log::channel('review')->error($message, $sanitized);
    }

    /**
     * 支付模块日志
     */
    public static function paymentDebug(string $message, array $context = []): void
    {
        $sanitized = self::sanitizeContext($context);
        Log::channel('payment_debug')->debug($message, $sanitized);
        Log::channel('payment')->debug($message, $sanitized);
    }

    public static function paymentInfo(string $message, array $context = []): void
    {
        $sanitized = self::sanitizeContext($context);
        Log::channel('payment_info')->info($message, $sanitized);
        Log::channel('payment')->info($message, $sanitized);
    }

    public static function paymentWarning(string $message, array $context = []): void
    {
        $sanitized = self::sanitizeContext($context);
        Log::channel('payment_warning')->warning($message, $sanitized);
        Log::channel('payment')->warning($message, $sanitized);
    }

    public static function paymentError(string $message, array $context = []): void
    {
        $sanitized = self::sanitizeContext($context);
        Log::channel('payment_error')->error($message, $sanitized);
        Log::channel('payment')->error($message, $sanitized);
    }
}

