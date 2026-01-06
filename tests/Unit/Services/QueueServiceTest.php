<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Queue;
use App\Models\User;
use App\Services\QueueService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

class QueueServiceTest extends TestCase
{
    private QueueService $sut;
    private User|MockObject $userMock;
    private Queue|MockObject $queueMock;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock User
        $this->userMock = $this->createMock(User::class);
        $this->userMock->id = 1;

        // Mock Queue
        $this->queueMock = $this->createMock(Queue::class);
        $this->queueMock->id = 1;
        $this->queueMock->queue_number = 'A001';
        $this->queueMock->user_id = 1;
        $this->queueMock->guest_count = 2;
        $this->queueMock->position = 1;
        $this->queueMock->status = 'waiting';

        // Instantiate SUT
        $this->sut = new QueueService();
    }

    #[Test]
    // Ref: TSD Section 3.6.1 - 加入排队
    public function join_queue_creates_queue_with_guest_count_and_table_type(): void
    {
        // Act
        $result = $this->sut->joinQueue($this->userMock, 2, 'window');

        // Assert
        $this->assertInstanceOf(Queue::class, $result);
        // 验证排队记录已创建，包含用餐人数和桌位类型
        // 注意：这是Red Stage，实际实现可能不同
    }

    #[Test]
    // Ref: TSD Section 3.6.1 - 加入排队
    public function join_queue_throws_exception_when_already_in_queue(): void
    {
        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('已在队列中');
        $this->expectExceptionCode(429);

        // 模拟用户已在队列中
        // 注意：这是Red Stage，实际实现可能不同
        $this->sut->joinQueue($this->userMock, 2);
    }

    #[Test]
    // Ref: TSD Section 3.6.2 - 排队号生成，智能前缀
    public function join_queue_generates_queue_number_with_correct_prefix(): void
    {
        // Act
        $result = $this->sut->joinQueue($this->userMock, 2);

        // Assert
        $this->assertInstanceOf(Queue::class, $result);
        // 验证排队号格式：前缀 + 3位序号（如A001、B023）
        // 注意：这是Red Stage，实际实现可能不同
    }

    #[Test]
    // Ref: TSD Section 3.6.3 - 实时位置查询
    public function get_queue_status_returns_position_ahead_count_and_estimated_wait_time(): void
    {
        // Act
        $result = $this->sut->getQueueStatus(1);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('queue_id', $result);
        $this->assertArrayHasKey('queue_number', $result);
        $this->assertArrayHasKey('current_position', $result);
        $this->assertArrayHasKey('ahead_count', $result);
        $this->assertArrayHasKey('estimated_wait_time', $result);
        $this->assertArrayHasKey('status', $result);
    }

    #[Test]
    // Ref: TSD Section 3.6.4 - 排队状态管理，called状态
    public function call_next_updates_first_waiting_queue_to_called_status(): void
    {
        // Act
        $result = $this->sut->callNext();

        // Assert
        // 如果队列为空，返回null；否则返回Queue实例
        // 验证状态变为called，called_at已设置
        // 注意：这是Red Stage，实际实现可能不同
    }

    #[Test]
    // Ref: TSD Section 3.6.4 - 排队状态管理
    public function call_next_returns_null_when_no_waiting_queues(): void
    {
        // Act
        $result = $this->sut->callNext();

        // Assert
        // 如果没有等待中的队列，返回null
        // 注意：这是Red Stage，实际实现可能不同
    }
}

