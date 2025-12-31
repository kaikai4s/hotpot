<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\AuditLogService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogAdminActivity
{
    public function __construct(
        private AuditLogService $auditLogService
    ) {
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // 只记录后台管理API的操作
        if ($request->is('api/admin/*')) {
            // 异步记录日志，避免影响响应速度
            try {
                $this->auditLogService->logHttpRequest($request, $response);
            } catch (\Exception $e) {
                // 日志记录失败不应影响主流程
                \Log::error('记录操作日志失败', [
                    'error' => $e->getMessage(),
                    'path' => $request->path(),
                ]);
            }
        }

        return $response;
    }
}

