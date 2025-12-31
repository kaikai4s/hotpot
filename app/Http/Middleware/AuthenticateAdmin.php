<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 尝试从请求头获取Bearer Token
        $token = $request->bearerToken();

        if ($token) {
            // 查找对应的PersonalAccessToken
            $accessToken = PersonalAccessToken::findToken($token);

            if ($accessToken) {
                // 检查tokenable类型是否为Admin
                $tokenableType = $accessToken->tokenable_type;
                $isAdmin = $tokenableType === \App\Models\Admin::class || 
                          $tokenableType === 'App\\Models\\Admin';
                
                if ($isAdmin) {
                    // 检查token是否过期（expires_at为null表示永不过期）
                    $isValid = $accessToken->expires_at === null || 
                              ($accessToken->expires_at && $accessToken->expires_at->isFuture());
                    
                    if ($isValid) {
                        // 确保加载tokenable关系
                        if (!$accessToken->relationLoaded('tokenable')) {
                            $accessToken->load('tokenable');
                        }
                        
                        if ($accessToken->tokenable) {
                            // 设置当前认证用户为Admin
                            Auth::guard('admin')->setUser($accessToken->tokenable);
                        }
                    }
                }
            }
        }

        // 如果admin guard没有用户，则返回401
        if (!Auth::guard('admin')->check()) {
            return response()->json([
                'code' => 401,
                'message' => '未登录',
            ], 401);
        }

        return $next($request);
    }
}

