<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Helpers\LoggerHelper;
use App\Http\Controllers\Controller;
use App\Models\Configuration;
use App\Models\MemberPoint;
use App\Models\User;
use App\Services\PhoneVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function wechatLogin(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string',
            'invite_code' => 'nullable|string|max:50',
        ]);

        $code = $request->input('code');
        $inviteCode = $request->input('invite_code');

        try {
            // 从配置中获取微信登录模式
            $loginMode = Configuration::getValue('wechat_login_mode', 'mock');
            
            // 根据配置决定使用模拟还是真实微信登录
            if ($loginMode === 'mock' || str_starts_with($code, 'mock_')) {
                // 模拟微信登录：使用code生成固定的openid（相同code会得到相同用户）
                // 这样每次使用相同的模拟code会登录到同一个用户
                $openid = 'mock_' . md5($code);
                $nickname = '微信用户' . strtoupper(substr(md5($code), 0, 6));
                $avatarUrl = null;
                LoggerHelper::userDebug('使用模拟微信登录', [
                    'code' => substr($code, 0, 10) . '...',
                    'openid' => $openid,
                    'login_mode' => $loginMode,
                ]);
            } else {
                // 真实微信登录流程
                // 从配置中获取微信AppID和Secret
                $appId = Configuration::getValue('wechat_app_id') ?: config('services.wechat.app_id');
                $appSecret = Configuration::getValue('wechat_app_secret') ?: config('services.wechat.app_secret');
                
                if (!$appId || !$appSecret) {
                    // 如果没有配置微信AppID和Secret，使用简化处理（开发环境）
                    $openid = 'dev_' . md5($code . time());
                    $nickname = '用户' . Str::random(6);
                    $avatarUrl = null;
                } else {
                    // 调用微信API获取access_token
                    $tokenUrl = "https://api.weixin.qq.com/sns/oauth2/access_token?appid={$appId}&secret={$appSecret}&code={$code}&grant_type=authorization_code";
                    
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $tokenUrl);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    $tokenResponse = curl_exec($ch);
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    curl_close($ch);
                    
                    if ($httpCode !== 200) {
                        LoggerHelper::userError('微信API请求失败', [
                            'http_code' => $httpCode,
                            'app_id' => substr($appId, 0, 10) . '...',
                        ]);
                        return response()->json([
                            'code' => 500,
                            'message' => '微信API请求失败',
                        ], 500);
                    }
                    
                    $tokenData = json_decode($tokenResponse, true);
                    
                    if (isset($tokenData['errcode'])) {
                        LoggerHelper::userError('微信登录失败', [
                            'errcode' => $tokenData['errcode'],
                            'errmsg' => $tokenData['errmsg'] ?? '未知错误',
                        ]);
                        return response()->json([
                            'code' => 400,
                            'message' => '微信登录失败：' . ($tokenData['errmsg'] ?? '未知错误'),
                        ], 400);
                    }
                    
                    $accessToken = $tokenData['access_token'];
                    $openid = $tokenData['openid'];
                    
                    // 获取用户信息
                    $userInfoUrl = "https://api.weixin.qq.com/sns/userinfo?access_token={$accessToken}&openid={$openid}&lang=zh_CN";
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $userInfoUrl);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    $userInfoResponse = curl_exec($ch);
                    curl_close($ch);
                    
                    $userInfoData = json_decode($userInfoResponse, true);
                    
                    if (isset($userInfoData['errcode'])) {
                        // 如果获取用户信息失败，只使用openid创建用户
                        $nickname = '微信用户' . Str::random(6);
                        $avatarUrl = null;
                    } else {
                        $nickname = $userInfoData['nickname'] ?? '微信用户' . Str::random(6);
                        $avatarUrl = $userInfoData['headimgurl'] ?? null;
                    }
                }
            }

            // 创建或更新用户
            $user = User::firstOrCreate(
                ['openid' => $openid],
                [
                    'nickname' => $nickname,
                    'avatar_url' => $avatarUrl,
                ]
            );
            
            if ($user->wasRecentlyCreated) {
                LoggerHelper::userInfo('新用户注册', [
                    'user_id' => $user->id,
                    'openid' => $openid,
                    'nickname' => $nickname,
                    'invite_code' => $inviteCode,
                ]);
            } else {
                LoggerHelper::userDebug('用户登录', [
                    'user_id' => $user->id,
                    'openid' => $openid,
                    'nickname' => $nickname,
                ]);
            }
            
            // 如果用户已存在，更新昵称和头像（如果微信返回了新信息）
            if (!$user->wasRecentlyCreated && isset($nickname)) {
                $user->nickname = $nickname;
                if (isset($avatarUrl)) {
                    $user->avatar_url = $avatarUrl;
                }
                $user->save();
            }

            // 如果是新用户且提供了邀请码，处理邀请关系
            if ($user->wasRecentlyCreated && $inviteCode) {
                try {
                    $invitationService = app(\App\Services\InvitationService::class);
                    $invitationService->registerWithInviteCode($user, $inviteCode);
                } catch (\Exception $e) {
                    // 邀请码处理失败不影响登录，只记录日志
                    LoggerHelper::userWarning('处理邀请码失败', [
                        'user_id' => $user->id,
                        'invite_code' => $inviteCode,
                        'error' => $e->getMessage(),
                    ]);
                    \Illuminate\Support\Facades\Log::warning('处理邀请码失败', [
                        'user_id' => $user->id,
                        'invite_code' => $inviteCode,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $token = $user->createToken('wechat-web')->plainTextToken;

            LoggerHelper::userInfo('用户登录成功', [
                'user_id' => $user->id,
                'nickname' => $user->nickname,
                'is_new_user' => $user->wasRecentlyCreated,
            ]);

            return response()->json([
                'code' => 200,
                'message' => '登录成功',
                'data' => [
                    'token' => $token,
                    'user' => [
                        'id' => $user->id,
                        'nickname' => $user->nickname,
                        'avatar_url' => $user->avatar_url,
                        'gender' => $user->gender,
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            LoggerHelper::userError('微信登录失败', [
                'code' => substr($code, 0, 10) . '...',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'code' => 500,
                'message' => '微信登录失败：' . $e->getMessage(),
            ], 500);
        }
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if (!$user) {
            LoggerHelper::userWarning('获取用户信息失败：用户未登录');
            return response()->json([
                'code' => 401,
                'message' => '未登录',
            ], 401);
        }
        
        // 预加载关系，避免N+1查询问题
        if (!$user->relationLoaded('memberPoints')) {
            $user->load('memberPoints');
        }
        
        // 获取用户段位 - 使用预加载的数据和缓存
        $memberPoint = $user->memberPoints;
        $level = null;
        if ($memberPoint && $memberPoint->level) {
            // 使用缓存，段位数据不会频繁变化，缓存1小时
            $level = Cache::remember(
                "point_level:{$memberPoint->level}",
                3600,
                function () use ($memberPoint) {
                    return \App\Models\PointLevel::where('code', $memberPoint->level)->first();
                }
            );
        }

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'nickname' => $user->nickname,
                    'avatar_url' => $user->avatar_url,
                    'phone' => $user->phone,
                    'gender' => $user->gender,
                    'equipped_title' => $user->equipped_title,
                    'has_password' => !empty($user->password), // 返回是否已设置密码
                    'level' => $level ? [
                        'code' => $level->code,
                        'name' => $level->name,
                        'icon' => $level->icon,
                        'color' => $level->color,
                    ] : null,
                ],
            ],
        ]);
    }

    /**
     * 发送手机验证码
     */
    public function sendPhoneCode(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'required|string|regex:/^1[3-9]\d{9}$/',
            'type' => 'nullable|string|in:login,register,reset_password',
        ]);

        $phone = $request->input('phone');
        $type = $request->input('type', 'login');
        $ipAddress = $request->ip();

        $phoneVerificationService = app(PhoneVerificationService::class);
        $result = $phoneVerificationService->sendCode($phone, $type, $ipAddress);

        if (!$result['success']) {
            return response()->json([
                'code' => 400,
                'message' => $result['message'],
            ], 400);
        }

        return response()->json([
            'code' => 200,
            'message' => $result['message'],
            'data' => [
                'code' => $result['code'] ?? null, // 开发环境返回验证码
            ],
        ]);
    }

    /**
     * 手机号+验证码登录
     */
    public function phoneCodeLogin(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'required|string|regex:/^1[3-9]\d{9}$/',
            'code' => 'required|string|size:6',
        ]);

        // 检查是否启用手机号登录
        // 注意：Configuration::getValue() 对于 boolean 类型会返回布尔值，不是字符串
        $phoneLoginEnabled = Configuration::getValue('phone_login_enabled', false);
        if (!$phoneLoginEnabled) {
            return response()->json([
                'code' => 403,
                'message' => '手机号登录功能未启用',
            ], 403);
        }

        $phone = $request->input('phone');
        $code = $request->input('code');

        // 验证验证码
        $phoneVerificationService = app(PhoneVerificationService::class);
        $verifyResult = $phoneVerificationService->verifyCode($phone, $code, 'login');

        if (!$verifyResult['success']) {
            return response()->json([
                'code' => 400,
                'message' => $verifyResult['message'],
            ], 400);
        }

        // 查找或创建用户
        $user = User::where('phone', $phone)->first();

        if (!$user) {
            // 如果用户不存在，创建新用户
            $user = User::create([
                'openid' => 'phone_' . md5($phone . time()),
                'phone' => $phone,
                'nickname' => '用户' . substr($phone, -4),
            ]);

            LoggerHelper::userInfo('手机号注册新用户', [
                'user_id' => $user->id,
                'phone' => substr($phone, 0, 3) . '****' . substr($phone, -4),
            ]);
        } else {
            LoggerHelper::userInfo('手机号登录', [
                'user_id' => $user->id,
                'phone' => substr($phone, 0, 3) . '****' . substr($phone, -4),
            ]);
        }

        // 生成Token
        $token = $user->createToken('phone-web')->plainTextToken;

        // 预加载关系，避免后续API调用时的N+1查询问题
        if (!$user->relationLoaded('memberPoints')) {
            $user->load('memberPoints');
        }

        // 获取用户段位信息
        $memberPoint = $user->memberPoints;
        
        // 【修复】如果用户没有MemberPoint，立即创建，避免后续API调用时的性能问题
        // 手机验证码登录的新用户可能没有MemberPoint（因为UserObserver中没有创建MemberPoint的逻辑）
        if (!$memberPoint) {
            $memberPoint = MemberPoint::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'points' => 0,
                    'level' => 'bronze',
                ]
            );
            // 重新加载关系
            $user->load('memberPoints');
            $memberPoint = $user->memberPoints;
        }
        
        $level = null;
        if ($memberPoint) {
            // 使用缓存，段位数据不会频繁变化，缓存1小时
            $level = Cache::remember(
                "point_level:{$memberPoint->level}",
                3600,
                function () use ($memberPoint) {
                    return \App\Models\PointLevel::where('code', $memberPoint->level)->first();
                }
            );
        }

        return response()->json([
            'code' => 200,
            'message' => '登录成功',
            'data' => [
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'nickname' => $user->nickname,
                    'avatar_url' => $user->avatar_url,
                    'phone' => $user->phone,
                    'gender' => $user->gender,
                    'equipped_title' => $user->equipped_title,
                    'has_password' => !empty($user->password), // 返回是否已设置密码
                    'level' => $level ? [
                        'code' => $level->code,
                        'name' => $level->name,
                        'icon' => $level->icon,
                        'color' => $level->color,
                    ] : null,
                ],
            ],
        ]);
    }

    /**
     * 手机号+密码登录
     */
    public function phonePasswordLogin(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'required|string|regex:/^1[3-9]\d{9}$/',
            'password' => 'required|string|min:6',
        ]);

        // 检查是否启用手机号登录
        // 注意：Configuration::getValue() 对于 boolean 类型会返回布尔值，不是字符串
        $phoneLoginEnabled = Configuration::getValue('phone_login_enabled', false);
        if (!$phoneLoginEnabled) {
            return response()->json([
                'code' => 403,
                'message' => '手机号登录功能未启用',
            ], 403);
        }

        $phone = $request->input('phone');
        $password = $request->input('password');

        // 查找用户
        $user = User::where('phone', $phone)->first();

        if (!$user) {
            LoggerHelper::userWarning('手机号登录失败：用户不存在', [
                'phone' => substr($phone, 0, 3) . '****' . substr($phone, -4),
            ]);
            return response()->json([
                'code' => 400,
                'message' => '手机号或密码错误',
            ], 400);
        }

        // 检查密码
        if (!$user->password || !Hash::check($password, $user->password)) {
            LoggerHelper::userWarning('手机号登录失败：密码错误', [
                'user_id' => $user->id,
                'phone' => substr($phone, 0, 3) . '****' . substr($phone, -4),
            ]);
            return response()->json([
                'code' => 400,
                'message' => '手机号或密码错误',
            ], 400);
        }

        // 检查用户是否激活
        if (!$user->is_active) {
            LoggerHelper::userWarning('手机号登录失败：用户已被禁用', [
                'user_id' => $user->id,
            ]);
            return response()->json([
                'code' => 403,
                'message' => '该账号已被禁用',
            ], 403);
        }

        LoggerHelper::userInfo('手机号密码登录成功', [
            'user_id' => $user->id,
            'phone' => substr($phone, 0, 3) . '****' . substr($phone, -4),
        ]);

        // 预加载关系，避免后续API调用时的N+1查询问题
        if (!$user->relationLoaded('memberPoints')) {
            $user->load('memberPoints');
        }

        // 获取用户段位信息
        $memberPoint = $user->memberPoints;
        
        // 【修复】如果用户没有MemberPoint，立即创建，避免后续API调用时的性能问题
        // 账号密码登录的用户可能没有MemberPoint（因为不是新用户，不会触发UserObserver）
        if (!$memberPoint) {
            // 使用缓存获取默认段位，避免重复查询
            $defaultLevelCode = Cache::remember(
                'point_level_default',
                3600,
                function () {
                    $defaultLevel = \App\Models\PointLevel::getActiveLevels()
                        ->sortBy('min_points')
                        ->first();
                    return $defaultLevel ? $defaultLevel->code : 'bronze';
                }
            );
            
            $memberPoint = MemberPoint::create([
                'user_id' => $user->id,
                'total_points' => 0,
                'available_points' => 0,
                'frozen_points' => 0,
                'level' => $defaultLevelCode,
            ]);
            
            // 重新加载关系
            $user->load('memberPoints');
        }
        
        $level = null;
        if ($memberPoint && $memberPoint->level) {
            $level = Cache::remember(
                "point_level:{$memberPoint->level}",
                3600,
                function () use ($memberPoint) {
                    return \App\Models\PointLevel::where('code', $memberPoint->level)->first();
                }
            );
        }

        // 生成Token
        $token = $user->createToken('phone-web')->plainTextToken;

        return response()->json([
            'code' => 200,
            'message' => '登录成功',
            'data' => [
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'nickname' => $user->nickname,
                    'avatar_url' => $user->avatar_url,
                    'phone' => $user->phone,
                    'equipped_title' => $user->equipped_title,
                    'has_password' => !empty($user->password),
                    'level' => $level ? [
                        'code' => $level->code,
                        'name' => $level->name,
                        'icon' => $level->icon,
                        'color' => $level->color,
                    ] : null,
                ],
            ],
        ]);
    }

    /**
     * 账户名+密码登录（账户名可以是昵称或手机号）
     */
    public function accountPasswordLogin(Request $request): JsonResponse
    {
        $request->validate([
            'account' => 'required|string|max:64',
            'password' => 'required|string|min:6',
        ]);

        $account = $request->input('account');
        $password = $request->input('password');

        // 查找用户：优先使用昵称，如果没有找到则尝试手机号
        $user = User::where('nickname', $account)->first();
        
        if (!$user) {
            // 如果昵称没找到，尝试作为手机号查找
            if (preg_match('/^1[3-9]\d{9}$/', $account)) {
                $user = User::where('phone', $account)->first();
            }
        }

        if (!$user) {
            LoggerHelper::userWarning('账户名登录失败：用户不存在', [
                'account' => substr($account, 0, 3) . '****',
            ]);
            return response()->json([
                'code' => 400,
                'message' => '账户名或密码错误',
            ], 400);
        }

        // 检查密码
        if (!$user->password || !Hash::check($password, $user->password)) {
            LoggerHelper::userWarning('账户名登录失败：密码错误', [
                'user_id' => $user->id,
                'account' => substr($account, 0, 3) . '****',
            ]);
            return response()->json([
                'code' => 400,
                'message' => '账户名或密码错误',
            ], 400);
        }

        // 检查用户是否激活
        if (!$user->is_active) {
            LoggerHelper::userWarning('账户名登录失败：用户已被禁用', [
                'user_id' => $user->id,
            ]);
            return response()->json([
                'code' => 403,
                'message' => '该账号已被禁用',
            ], 403);
        }

        LoggerHelper::userInfo('账户名密码登录成功', [
            'user_id' => $user->id,
            'account' => substr($account, 0, 3) . '****',
        ]);

        // 预加载关系，避免后续API调用时的N+1查询问题
        if (!$user->relationLoaded('memberPoints')) {
            $user->load('memberPoints');
        }

        // 获取用户段位信息
        $memberPoint = $user->memberPoints;
        
        // 【修复】如果用户没有MemberPoint，立即创建，避免后续API调用时的性能问题
        // 账号密码登录的用户可能没有MemberPoint（因为不是新用户，不会触发UserObserver）
        if (!$memberPoint) {
            // 使用缓存获取默认段位，避免重复查询
            $defaultLevelCode = Cache::remember(
                'point_level_default',
                3600,
                function () {
                    $defaultLevel = \App\Models\PointLevel::getActiveLevels()
                        ->sortBy('min_points')
                        ->first();
                    return $defaultLevel ? $defaultLevel->code : 'bronze';
                }
            );
            
            $memberPoint = MemberPoint::create([
                'user_id' => $user->id,
                'total_points' => 0,
                'available_points' => 0,
                'frozen_points' => 0,
                'level' => $defaultLevelCode,
            ]);
            
            // 重新加载关系
            $user->load('memberPoints');
        }
        
        $level = null;
        if ($memberPoint && $memberPoint->level) {
            $level = Cache::remember(
                "point_level:{$memberPoint->level}",
                3600,
                function () use ($memberPoint) {
                    return \App\Models\PointLevel::where('code', $memberPoint->level)->first();
                }
            );
        }

        // 生成Token
        $token = $user->createToken('account-web')->plainTextToken;

        return response()->json([
            'code' => 200,
            'message' => '登录成功',
            'data' => [
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'nickname' => $user->nickname,
                    'avatar_url' => $user->avatar_url,
                    'phone' => $user->phone,
                    'gender' => $user->gender,
                    'equipped_title' => $user->equipped_title,
                    'has_password' => !empty($user->password),
                    'level' => $level ? [
                        'code' => $level->code,
                        'name' => $level->name,
                        'icon' => $level->icon,
                        'color' => $level->color,
                    ] : null,
                ],
            ],
        ]);
    }

    /**
     * 用户退出登录
     */
    public function logout(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        if ($user) {
            LoggerHelper::userInfo('用户退出登录', [
                'user_id' => $user->id,
                'nickname' => $user->nickname,
            ]);
            // 删除当前使用的token
            $user->currentAccessToken()?->delete();
        } else {
            LoggerHelper::userWarning('退出登录失败：用户未登录');
        }

        return response()->json([
            'code' => 200,
            'message' => '退出成功',
        ]);
    }
}
