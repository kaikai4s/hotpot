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
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        
        LoggerHelper::userDebug('获取用户信息', [
            'user_id' => $user->id,
            'nickname' => $user->nickname,
        ]);
        
        // 获取用户段位
        $memberPoint = $user->memberPoints;
        $level = null;
        if ($memberPoint) {
            $level = \App\Models\PointLevel::where('code', $memberPoint->level)->first();
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
                    'equipped_title' => $user->equipped_title,
                    'level' => $level ? [
                        'code' => $level->code,
                        'name' => $level->name,
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
