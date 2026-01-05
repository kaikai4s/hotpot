<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogUdpHandler;
use Monolog\Processor\PsrLogMessageProcessor;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Log Channel
    |--------------------------------------------------------------------------
    |
    | This option defines the default log channel that gets used when writing
    | messages to the logs. The name specified in this option should match
    | one of the channels defined in the "channels" configuration array.
    |
    */

    'default' => env('LOG_CHANNEL', 'stack'),

    /*
    |--------------------------------------------------------------------------
    | Deprecations Log Channel
    |--------------------------------------------------------------------------
    |
    | This option controls the log channel that should be used to log warnings
    | regarding deprecated PHP and library features. This allows you to get your
    | application ready for upcoming major versions of dependencies.
    |
    */

    'deprecations' => [
        'channel' => env('LOG_DEPRECATIONS_CHANNEL', 'null'),
        'trace' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Log Channels
    |--------------------------------------------------------------------------
    |
    | Here you may configure the log channels for your application. Out of
    | the box, Laravel uses the Monolog PHP logging library. This gives
    | you a variety of powerful log handlers / formatters to utilize.
    |
    | Available Drivers: "single", "daily", "slack", "syslog",
    |                    "errorlog", "monolog",
    |                    "custom", "stack"
    |
    */

    'channels' => [
        'stack' => [
            'driver' => 'stack',
            'channels' => ['single'],
            'ignore_exceptions' => false,
        ],

        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],

        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 14,
            'replace_placeholders' => true,
        ],

        'slack' => [
            'driver' => 'slack',
            'url' => env('LOG_SLACK_WEBHOOK_URL'),
            'username' => 'Laravel Log',
            'emoji' => ':boom:',
            'level' => env('LOG_LEVEL', 'critical'),
            'replace_placeholders' => true,
        ],

        'papertrail' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => env('LOG_PAPERTRAIL_HANDLER', SyslogUdpHandler::class),
            'handler_with' => [
                'host' => env('PAPERTRAIL_URL'),
                'port' => env('PAPERTRAIL_PORT'),
                'connectionString' => 'tls://'.env('PAPERTRAIL_URL').':'.env('PAPERTRAIL_PORT'),
            ],
            'processors' => [PsrLogMessageProcessor::class],
        ],

        'stderr' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => StreamHandler::class,
            'formatter' => env('LOG_STDERR_FORMATTER'),
            'with' => [
                'stream' => 'php://stderr',
            ],
            'processors' => [PsrLogMessageProcessor::class],
        ],

        'syslog' => [
            'driver' => 'syslog',
            'level' => env('LOG_LEVEL', 'debug'),
            'facility' => LOG_USER,
            'replace_placeholders' => true,
        ],

        'errorlog' => [
            'driver' => 'errorlog',
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],

        'null' => [
            'driver' => 'monolog',
            'handler' => NullHandler::class,
        ],

        'emergency' => [
            'path' => storage_path('logs/laravel.log'),
        ],

        // 订单模块日志
        'order' => [
            'driver' => 'daily',
            'path' => storage_path('logs/order/order.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 30,
            'replace_placeholders' => true,
        ],
        'order_debug' => [
            'driver' => 'daily',
            'path' => storage_path('logs/order/debug.log'),
            'level' => 'debug',
            'days' => 7,
            'replace_placeholders' => true,
        ],
        'order_info' => [
            'driver' => 'daily',
            'path' => storage_path('logs/order/info.log'),
            'level' => 'info',
            'days' => 30,
            'replace_placeholders' => true,
        ],
        'order_warning' => [
            'driver' => 'daily',
            'path' => storage_path('logs/order/warning.log'),
            'level' => 'warning',
            'days' => 60,
            'replace_placeholders' => true,
        ],
        'order_error' => [
            'driver' => 'daily',
            'path' => storage_path('logs/order/error.log'),
            'level' => 'error',
            'days' => 90,
            'replace_placeholders' => true,
        ],

        // 定金模块日志
        'deposit' => [
            'driver' => 'daily',
            'path' => storage_path('logs/deposit/deposit.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 30,
            'replace_placeholders' => true,
        ],
        'deposit_debug' => [
            'driver' => 'daily',
            'path' => storage_path('logs/deposit/debug.log'),
            'level' => 'debug',
            'days' => 7,
            'replace_placeholders' => true,
        ],
        'deposit_info' => [
            'driver' => 'daily',
            'path' => storage_path('logs/deposit/info.log'),
            'level' => 'info',
            'days' => 30,
            'replace_placeholders' => true,
        ],
        'deposit_warning' => [
            'driver' => 'daily',
            'path' => storage_path('logs/deposit/warning.log'),
            'level' => 'warning',
            'days' => 60,
            'replace_placeholders' => true,
        ],
        'deposit_error' => [
            'driver' => 'daily',
            'path' => storage_path('logs/deposit/error.log'),
            'level' => 'error',
            'days' => 90,
            'replace_placeholders' => true,
        ],

        // 用户模块日志
        'user' => [
            'driver' => 'daily',
            'path' => storage_path('logs/user/user.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 30,
            'replace_placeholders' => true,
        ],
        'user_debug' => [
            'driver' => 'daily',
            'path' => storage_path('logs/user/debug.log'),
            'level' => 'debug',
            'days' => 7,
            'replace_placeholders' => true,
        ],
        'user_info' => [
            'driver' => 'daily',
            'path' => storage_path('logs/user/info.log'),
            'level' => 'info',
            'days' => 30,
            'replace_placeholders' => true,
        ],
        'user_warning' => [
            'driver' => 'daily',
            'path' => storage_path('logs/user/warning.log'),
            'level' => 'warning',
            'days' => 60,
            'replace_placeholders' => true,
        ],
        'user_error' => [
            'driver' => 'daily',
            'path' => storage_path('logs/user/error.log'),
            'level' => 'error',
            'days' => 90,
            'replace_placeholders' => true,
        ],

        // 桌位模块日志
        'table' => [
            'driver' => 'daily',
            'path' => storage_path('logs/table/table.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 30,
            'replace_placeholders' => true,
        ],
        'table_debug' => [
            'driver' => 'daily',
            'path' => storage_path('logs/table/debug.log'),
            'level' => 'debug',
            'days' => 7,
            'replace_placeholders' => true,
        ],
        'table_info' => [
            'driver' => 'daily',
            'path' => storage_path('logs/table/info.log'),
            'level' => 'info',
            'days' => 30,
            'replace_placeholders' => true,
        ],
        'table_warning' => [
            'driver' => 'daily',
            'path' => storage_path('logs/table/warning.log'),
            'level' => 'warning',
            'days' => 60,
            'replace_placeholders' => true,
        ],
        'table_error' => [
            'driver' => 'daily',
            'path' => storage_path('logs/table/error.log'),
            'level' => 'error',
            'days' => 90,
            'replace_placeholders' => true,
        ],

        // 积分模块日志
        'point' => [
            'driver' => 'daily',
            'path' => storage_path('logs/point/point.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 30,
            'replace_placeholders' => true,
        ],
        'point_debug' => [
            'driver' => 'daily',
            'path' => storage_path('logs/point/debug.log'),
            'level' => 'debug',
            'days' => 7,
            'replace_placeholders' => true,
        ],
        'point_info' => [
            'driver' => 'daily',
            'path' => storage_path('logs/point/info.log'),
            'level' => 'info',
            'days' => 30,
            'replace_placeholders' => true,
        ],
        'point_warning' => [
            'driver' => 'daily',
            'path' => storage_path('logs/point/warning.log'),
            'level' => 'warning',
            'days' => 60,
            'replace_placeholders' => true,
        ],
        'point_error' => [
            'driver' => 'daily',
            'path' => storage_path('logs/point/error.log'),
            'level' => 'error',
            'days' => 90,
            'replace_placeholders' => true,
        ],

        // 预约模块日志
        'reservation' => [
            'driver' => 'daily',
            'path' => storage_path('logs/reservation/reservation.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 30,
            'replace_placeholders' => true,
        ],
        'reservation_debug' => [
            'driver' => 'daily',
            'path' => storage_path('logs/reservation/debug.log'),
            'level' => 'debug',
            'days' => 7,
            'replace_placeholders' => true,
        ],
        'reservation_info' => [
            'driver' => 'daily',
            'path' => storage_path('logs/reservation/info.log'),
            'level' => 'info',
            'days' => 30,
            'replace_placeholders' => true,
        ],
        'reservation_warning' => [
            'driver' => 'daily',
            'path' => storage_path('logs/reservation/warning.log'),
            'level' => 'warning',
            'days' => 60,
            'replace_placeholders' => true,
        ],
        'reservation_error' => [
            'driver' => 'daily',
            'path' => storage_path('logs/reservation/error.log'),
            'level' => 'error',
            'days' => 90,
            'replace_placeholders' => true,
        ],

        // 评价模块日志
        'review' => [
            'driver' => 'daily',
            'path' => storage_path('logs/review/review.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 30,
            'replace_placeholders' => true,
        ],
        'review_debug' => [
            'driver' => 'daily',
            'path' => storage_path('logs/review/debug.log'),
            'level' => 'debug',
            'days' => 7,
            'replace_placeholders' => true,
        ],
        'review_info' => [
            'driver' => 'daily',
            'path' => storage_path('logs/review/info.log'),
            'level' => 'info',
            'days' => 30,
            'replace_placeholders' => true,
        ],
        'review_warning' => [
            'driver' => 'daily',
            'path' => storage_path('logs/review/warning.log'),
            'level' => 'warning',
            'days' => 60,
            'replace_placeholders' => true,
        ],
        'review_error' => [
            'driver' => 'daily',
            'path' => storage_path('logs/review/error.log'),
            'level' => 'error',
            'days' => 90,
            'replace_placeholders' => true,
        ],

        // 支付模块日志
        'payment' => [
            'driver' => 'daily',
            'path' => storage_path('logs/payment/payment.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 30,
            'replace_placeholders' => true,
        ],
        'payment_debug' => [
            'driver' => 'daily',
            'path' => storage_path('logs/payment/debug.log'),
            'level' => 'debug',
            'days' => 7,
            'replace_placeholders' => true,
        ],
        'payment_info' => [
            'driver' => 'daily',
            'path' => storage_path('logs/payment/info.log'),
            'level' => 'info',
            'days' => 30,
            'replace_placeholders' => true,
        ],
        'payment_warning' => [
            'driver' => 'daily',
            'path' => storage_path('logs/payment/warning.log'),
            'level' => 'warning',
            'days' => 60,
            'replace_placeholders' => true,
        ],
        'payment_error' => [
            'driver' => 'daily',
            'path' => storage_path('logs/payment/error.log'),
            'level' => 'error',
            'days' => 90,
            'replace_placeholders' => true,
        ],
    ],

];

