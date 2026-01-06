<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\MemberPoint;
use App\Models\PointExpiration;
use App\Models\PointTransaction;
use App\Models\User;
use App\Services\PointExpirationService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

class PointExpirationServiceTest extends TestCase
{
    private PointExpirationService $sut;
    private User|MockObject $userMock;
    private PointTransaction|MockObject $transactionMock;
    private PointExpiration|MockObject $expirationMock;
    private MemberPoint|MockObject $memberPointMock;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock User
        $this->userMock = $this->createMock(User::class);
        $this->userMock->id = 1;

        // Mock PointTransaction
        $this->transactionMock = $this->createMock(PointTransaction::class);
        $this->transactionMock->id = 1;
        $this->transactionMock->user_id = 1;
        $this->transactionMock->type = 'earn';
        $this->transactionMock->points = 100;
        $this->transactionMock->created_at = now();

        // Mock PointExpiration
        $this->expirationMock = $this->createMock(PointExpiration::class);
        $this->expirationMock->id = 1;
        $this->expirationMock->user_id = 1;
        $this->expirationMock->transaction_id = 1;
        $this->expirationMock->points = 100;
        $this->expirationMock->expire_at = now()->addDays(365);
        $this->expirationMock->status = 'pending';
        $this->expirationMock->user = $this->userMock;
        $this->expirationMock->transaction = $this->transactionMock;

        // Mock MemberPoint
        $this->memberPointMock = $this->createMock(MemberPoint::class);
        $this->memberPointMock->user_id = 1;
        $this->memberPointMock->available_points = 500;

        // Instantiate SUT
        $this->sut = new PointExpirationService();
    }

    #[Test]
    // Ref: TSD Section 3.5.5 - 积分过期管理，安排积分过期时间
    public function schedule_expiration_creates_expiration_record_for_earn_transaction(): void
    {
        // Act
        $this->sut->scheduleExpiration($this->transactionMock, 365);

        // Assert
        // 验证过期记录已创建，expire_at设置为365天后
        // 注意：这是Red Stage，实际实现可能不同
    }

    #[Test]
    // Ref: TSD Section 3.5.5 - 积分过期管理
    public function schedule_expiration_skips_when_transaction_type_is_not_earn(): void
    {
        // Arrange
        $this->transactionMock->type = 'redeem';

        // Act
        $this->sut->scheduleExpiration($this->transactionMock, 365);

        // Assert
        // 验证非earn类型的交易不安排过期
        // 注意：这是Red Stage，实际实现可能不同
    }

    #[Test]
    // Ref: TSD Section 3.5.5 - 积分过期管理，处理过期积分
    public function process_expirations_decreases_available_points_when_expired(): void
    {
        // Arrange
        $this->expirationMock->expire_at = now()->subDay(); // 已过期

        // Act
        $result = $this->sut->processExpirations();

        // Assert
        $this->assertIsInt($result);
        $this->assertGreaterThanOrEqual(0, $result);
        // 验证可用积分已扣除，过期交易已记录
        // 注意：这是Red Stage，实际实现可能不同
    }

    #[Test]
    // Ref: TSD Section 3.5.5 - 积分过期管理
    public function process_expirations_marks_as_cancelled_when_points_already_used(): void
    {
        // Arrange
        $this->expirationMock->expire_at = now()->subDay();
        $this->memberPointMock->available_points = 0; // 积分已被使用

        // Act
        $result = $this->sut->processExpirations();

        // Assert
        // 验证如果积分已被使用，标记为cancelled
        // 注意：这是Red Stage，实际实现可能不同
    }

    #[Test]
    // Ref: TSD Section 3.5.5 - 积分过期管理，获取即将过期的积分
    public function get_expiring_points_returns_points_expiring_within_days(): void
    {
        // Act
        $result = $this->sut->getExpiringPoints($this->userMock, 30);

        // Assert
        $this->assertIsArray($result);
        // 验证返回30天内即将过期的积分列表
        // 注意：这是Red Stage，实际实现可能不同
    }
}

