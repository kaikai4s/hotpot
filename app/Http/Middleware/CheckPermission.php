<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$permissions
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        // 使用 admin guard 获取当前认证的管理员
        $admin = $request->user('admin');

        if (!$admin) {
            return response()->json([
                'code' => 401,
                'message' => '未登录',
            ], 401);
        }

        // 检查是否有任一权限
        if (!$admin->hasAnyPermission($permissions)) {
            return response()->json([
                'code' => 403,
                'message' => '没有权限执行此操作',
            ], 403);
        }

        return $next($request);
    }
}
