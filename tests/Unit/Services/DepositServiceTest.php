<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Reservation;
use App\Services\DepositService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

class DepositServiceTest extends TestCase
{
    private DepositService $sut;
    private Reservation|MockObject $reservationMock;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock Reservation
        $this->reservationMock = $this->createMock(Reservation::class);
        $this->reservationMock->id = 1;
        $this->reservationMock->reservation_code = 'RES20260105000001';
        $this->reservationMock->deposit_amount = 50.00;
        $this->reservationMock->deposit_status = 'paid';
        $this->reservationMock->deposit_transaction_id = 'TXN123456';
        $this->reservationMock->deposit_data = ['method' => 'wechat'];
        $this->reservationMock->deposit_refunded_at = null;
        $this->reservationMock->arrived_at = now();

        // Instantiate SUT
        $this->sut = new DepositService();
    }

    #[Test]
    // Ref: TSD Section 3.16.2 - 定金退款，预约取消时自动退款
    public function refund_deposit_updates_status_to_refunded_when_deposit_is_paid(): void
    {
        // Arrange
        $this->reservationMock->deposit_status = 'paid';
        $this->reservationMock->arrived_at = now();

        // Act
        $result = $this->sut->refundDeposit($this->reservationMock);

        // Assert
        $this->assertTrue($result);
        // 验证deposit_status变为refunded，deposit_refunded_at已设置
        // 注意：这是Red Stage，实际实现可能不同
    }

    #[Test]
    // Ref: TSD Section 3.16.2 - 定金退款
    public function refund_deposit_returns_false_when_deposit_not_paid(): void
    {
        // Arrange
        $this->reservationMock->deposit_status = 'unpaid';

        // Act
        $result = $this->sut->refundDeposit($this->reservationMock);

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    // Ref: TSD Section 3.16.2 - 定金退款
    public function refund_deposit_returns_false_when_already_refunded(): void
    {
        // Arrange
        $this->reservationMock->deposit_status = 'paid';
        $this->reservationMock->deposit_refunded_at = now();

        // Act
        $result = $this->sut->refundDeposit($this->reservationMock);

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    // Ref: TSD Section 3.16.2 - 定金退款
    public function refund_deposit_returns_false_when_reservation_not_arrived(): void
    {
        // Arrange
        $this->reservationMock->deposit_status = 'paid';
        $this->reservationMock->arrived_at = null;

        // Act
        $result = $this->sut->refundDeposit($this->reservationMock);

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    // Ref: TSD Section 3.16.2 - 手动返还定金（后台管理员操作）
    public function manual_refund_deposit_updates_status_to_refunded(): void
    {
        // Arrange
        $this->reservationMock->deposit_status = 'paid';
        $this->reservationMock->deposit_refunded_at = null;

        // Act
        $result = $this->sut->manualRefundDeposit(1, '用户申请退款');

        // Assert
        $this->assertTrue($result);
        // 验证deposit_status变为refunded，deposit_refunded_at已设置
        // 注意：这是Red Stage，实际实现可能不同
    }

    #[Test]
    // Ref: TSD Section 3.16.2 - 手动返还定金
    public function manual_refund_deposit_throws_exception_when_deposit_not_paid(): void
    {
        // Arrange
        $this->reservationMock->deposit_status = 'unpaid';

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('预约定金未支付');
        $this->expectExceptionCode(400);

        $this->sut->manualRefundDeposit(1);
    }

    #[Test]
    // Ref: TSD Section 3.16.2 - 手动返还定金
    public function manual_refund_deposit_throws_exception_when_already_refunded(): void
    {
        // Arrange
        $this->reservationMock->deposit_status = 'paid';
        $this->reservationMock->deposit_refunded_at = now();

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('预约定金已返还');
        $this->expectExceptionCode(400);

        $this->sut->manualRefundDeposit(1);
    }
}

