<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Services;

use App\Helpers\LoggerHelper;
use App\Models\Configuration;
use App\Models\PhoneVerificationCode;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PhoneVerificationService
{
    /**
     * 发送验证码
     */
    public function sendCode(string $phone, string $type = 'login', ?string $ipAddress = null): array
    {
        // 检查手机号格式
        if (!preg_match('/^1[3-9]\d{9}$/', $phone)) {
            return [
                'success' => false,
                'message' => '手机号格式不正确',
            ];
        }

        // 【修复】模拟绑定模式：如果启用模拟绑定且是注册类型，跳过手机号登录检查
        // 注意：Configuration::getValue() 对于 boolean 类型会返回布尔值，不是字符串
        $mockEnabled = Configuration::getValue('phone_verification_mock_enabled', false);
        $isMockBinding = $mockEnabled && $type === 'register';

        // 检查是否启用手机号登录（模拟绑定模式除外）
        if (!$isMockBinding) {
            $phoneLoginEnabled = Configuration::getValue('phone_login_enabled', false);
        if (!$phoneLoginEnabled) {
            return [
                'success' => false,
                'message' => '手机号登录功能未启用',
            ];
            }
        }

        // 检查发送频率限制（1分钟内只能发送一次）
        $cacheKey = "phone_verification_sent:{$phone}";
        if (Cache::has($cacheKey)) {
            return [
                'success' => false,
                'message' => '验证码发送过于频繁，请稍后再试',
            ];
        }

        // 【模拟绑定手机号】如果启用模拟模式且是注册类型，不生成和保存验证码
        if ($isMockBinding) {
            // 设置发送频率限制（1分钟）
            Cache::put($cacheKey, true, 60);
            
            // 模拟模式：不保存验证码，直接返回成功
            LoggerHelper::userInfo('发送手机验证码（模拟绑定模式）', [
                'phone' => substr($phone, 0, 3) . '****' . substr($phone, -4),
                'type' => $type,
                'mock_mode' => true,
            ]);
            
            return [
                'success' => true,
                'message' => '验证码已发送',
                'code' => null, // 模拟模式下不返回验证码
            ];
        }

        // 生成6位数字验证码
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // 获取验证码有效期（默认5分钟）
        $expiresMinutes = (int) Configuration::getValue('phone_verification_code_expires_minutes', 5);
        $expiresAt = now()->addMinutes($expiresMinutes);

        // 保存验证码到数据库
        PhoneVerificationCode::create([
            'phone' => $phone,
            'code' => $code,
            'type' => $type,
            'expires_at' => $expiresAt,
            'ip_address' => $ipAddress,
        ]);

        // 设置发送频率限制（1分钟）
        Cache::put($cacheKey, true, 60);

        // 发送短信（开发环境使用模拟，生产环境需要接入真实短信服务）
        $smsMode = Configuration::getValue('sms_mode', 'mock');
        if ($smsMode === 'mock') {
            // 模拟发送，只在日志中记录
            LoggerHelper::userInfo('发送手机验证码（模拟）', [
                'phone' => substr($phone, 0, 3) . '****' . substr($phone, -4),
                'code' => $code,
                'type' => $type,
                'expires_at' => $expiresAt->format('Y-m-d H:i:s'),
            ]);
            
            // 开发环境：将验证码记录到日志，方便测试
            Log::info('手机验证码（开发环境）', [
                'phone' => $phone,
                'code' => $code,
                'expires_at' => $expiresAt->format('Y-m-d H:i:s'),
            ]);
        } else {
            // 真实短信发送（需要接入短信服务商API）
            // TODO: 接入真实短信服务
            LoggerHelper::userInfo('发送手机验证码（真实）', [
                'phone' => substr($phone, 0, 3) . '****' . substr($phone, -4),
                'type' => $type,
            ]);
        }

        return [
            'success' => true,
            'message' => '验证码已发送',
            'code' => $smsMode === 'mock' ? $code : null, // 开发环境返回验证码，生产环境不返回
        ];
    }

    /**
     * 验证验证码
     */
    public function verifyCode(string $phone, string $code, string $type = 'login'): array
    {
        // 【模拟绑定手机号】如果启用模拟模式，接受任何验证码（仅用于绑定手机号）
        // 注意：Configuration::getValue() 对于 boolean 类型会返回布尔值，不是字符串
        $mockEnabled = Configuration::getValue('phone_verification_mock_enabled', false);
        if ($mockEnabled && $type === 'register') {
            // 模拟模式：接受任何验证码
            LoggerHelper::userInfo('验证码验证成功（模拟模式）', [
                'phone' => substr($phone, 0, 3) . '****' . substr($phone, -4),
                'type' => $type,
                'mock_mode' => true,
            ]);
            
            return [
                'success' => true,
                'message' => '验证码验证成功',
            ];
        }

        // 查找有效的验证码
        $verificationCode = PhoneVerificationCode::where('phone', $phone)
            ->where('code', $code)
            ->where('type', $type)
            ->where('is_used', false)
            ->where('expires_at', '>', now())
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$verificationCode) {
            LoggerHelper::userWarning('验证码验证失败', [
                'phone' => substr($phone, 0, 3) . '****' . substr($phone, -4),
                'code' => $code,
                'type' => $type,
            ]);
            return [
                'success' => false,
                'message' => '验证码错误或已过期',
            ];
        }

        // 标记验证码为已使用
        $verificationCode->markAsUsed();

        LoggerHelper::userInfo('验证码验证成功', [
            'phone' => substr($phone, 0, 3) . '****' . substr($phone, -4),
            'type' => $type,
        ]);

        return [
            'success' => true,
            'message' => '验证码验证成功',
        ];
    }

    /**
     * 清理过期的验证码
     */
    public function cleanExpiredCodes(): void
    {
        $deleted = PhoneVerificationCode::where('expires_at', '<', now())
            ->orWhere(function ($query) {
                $query->where('is_used', true)
                    ->where('used_at', '<', now()->subDays(7));
            })
            ->delete();

        if ($deleted > 0) {
            LoggerHelper::userInfo('清理过期验证码', [
                'deleted_count' => $deleted,
            ]);
        }
    }
}

