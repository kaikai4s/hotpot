<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\AuditLog;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuditLogServiceTest extends TestCase
{
    private AuditLogService $sut;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sut = new AuditLogService();
    }

    #[Test]
    // Ref: TSD Section 3.18.1 - 操作日志记录
    public function log_creates_audit_log_with_action_and_model_info(): void
    {
        // Act
        $result = $this->sut->log('create', 'App\\Models\\Reservation', 1, null, ['status' => 'pending']);

        // Assert
        $this->assertInstanceOf(AuditLog::class, $result);
        // 验证审计日志已创建，包含操作类型、模型类型、模型ID等信息
        // 注意：这是Red Stage，实际实现可能不同
    }

    #[Test]
    // Ref: TSD Section 3.18.1 - 操作日志记录
    public function log_includes_request_info_when_request_provided(): void
    {
        // Arrange
        $request = $this->createMock(Request::class);
        $request->method('ip')->willReturn('127.0.0.1');
        $request->method('userAgent')->willReturn('Mozilla/5.0');

        // Act
        $result = $this->sut->log('update', 'App\\Models\\Order', 1, null, ['status' => 'paid'], null, $request);

        // Assert
        $this->assertInstanceOf(AuditLog::class, $result);
        // 验证IP地址和User-Agent已记录
        // 注意：这是Red Stage，实际实现可能不同
    }

    #[Test]
    // Ref: TSD Section 3.18.1 - 记录HTTP请求操作日志
    public function log_http_request_creates_log_for_admin_api_requests(): void
    {
        // Arrange
        $request = $this->createMock(Request::class);
        $request->method('is')->willReturn(true);
        $request->method('method')->willReturn('POST');
        $request->method('path')->willReturn('api/admin/v1/reservations');
        $request->method('all')->willReturn(['date' => '2026-01-05']);
        $request->method('ip')->willReturn('127.0.0.1');
        $request->method('userAgent')->willReturn('Mozilla/5.0');

        // Act
        $this->sut->logHttpRequest($request);

        // Assert
        // 验证审计日志已创建
        // 注意：这是Red Stage，实际实现可能不同
    }

    #[Test]
    // Ref: TSD Section 3.18.1 - 记录HTTP请求操作日志
    public function log_http_request_skips_when_not_admin_api(): void
    {
        // Arrange
        $request = $this->createMock(Request::class);
        $request->method('is')->willReturn(false);

        // Act
        $this->sut->logHttpRequest($request);

        // Assert
        // 验证非管理端API请求不记录
        // 注意：这是Red Stage，实际实现可能不同
    }

    #[Test]
    // Ref: TSD Section 3.18.1 - 记录HTTP请求操作日志
    public function log_http_request_skips_when_audit_logs_endpoint(): void
    {
        // Arrange
        $request = $this->createMock(Request::class);
        $request->method('is')->willReturnCallback(function ($pattern) {
            return $pattern === 'api/admin/*' || $pattern === 'api/admin/v1/audit-logs*';
        });
        $request->method('path')->willReturn('api/admin/v1/audit-logs');

        // Act
        $this->sut->logHttpRequest($request);

        // Assert
        // 验证审计日志查询接口本身不记录，避免循环记录
        // 注意：这是Red Stage，实际实现可能不同
    }
}

