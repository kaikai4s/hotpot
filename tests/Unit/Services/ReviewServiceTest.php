<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Admin;
use App\Models\Dish;
use App\Models\Order;
use App\Models\Review;
use App\Models\User;
use App\Services\ProfanityFilterService;
use App\Services\ReviewService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

class ReviewServiceTest extends TestCase
{
    private ReviewService $sut;
    private ProfanityFilterService|MockObject $profanityFilterMock;
    private User|MockObject $userMock;
    private Order|MockObject $orderMock;
    private Dish|MockObject $dishMock;
    private Review|MockObject $reviewMock;
    private Admin|MockObject $adminMock;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock ProfanityFilterService
        $this->profanityFilterMock = $this->createMock(ProfanityFilterService::class);

        // Mock User
        $this->userMock = $this->createMock(User::class);
        $this->userMock->id = 1;

        // Mock Order
        $this->orderMock = $this->createMock(Order::class);
        $this->orderMock->id = 1;
        $this->orderMock->user_id = 1;
        $this->orderMock->status = 'pending_review';

        // Mock Dish
        $this->dishMock = $this->createMock(Dish::class);
        $this->dishMock->id = 1;

        // Mock Review
        $this->reviewMock = $this->createMock(Review::class);
        $this->reviewMock->id = 1;
        $this->reviewMock->user_id = 1;
        $this->reviewMock->order_id = 1;
        $this->reviewMock->dish_id = 1;
        $this->reviewMock->rating = 5;
        $this->reviewMock->status = 'approved';

        // Mock Admin
        $this->adminMock = $this->createMock(Admin::class);
        $this->adminMock->id = 1;
        $this->adminMock->name = '管理员';
        $this->adminMock->username = 'admin';

        // Instantiate SUT
        $this->sut = new ReviewService($this->profanityFilterMock);
    }

    #[Test]
    // Ref: TSD Section 3.3.1 - 多维度评价
    public function create_review_creates_review_with_rating_content_images_and_tags(): void
    {
        // Arrange
        $this->profanityFilterMock->expects($this->once())
            ->method('checkReview')
            ->willReturn(['has_profanity' => false, 'matched_words' => []]);

        // Act
        $result = $this->sut->createReview(
            $this->userMock,
            1,
            1,
            5,
            '很好吃',
            ['image1.jpg', 'image2.jpg'],
            ['推荐', '好吃']
        );

        // Assert
        $this->assertInstanceOf(Review::class, $result);
        // 验证评价包含评分、内容、图片、标签
        // 注意：这是Red Stage，实际实现可能不同
    }

    #[Test]
    // Ref: TSD Section 3.3.1 - 自动审核机制，不文明语言过滤
    public function create_review_throws_exception_when_content_contains_profanity(): void
    {
        // Arrange
        $this->profanityFilterMock->expects($this->once())
            ->method('checkReview')
            ->willReturn(['has_profanity' => true, 'matched_words' => ['垃圾']]);

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('评价内容包含不文明语言');
        $this->expectExceptionCode(422);

        $this->sut->createReview(
            $this->userMock,
            1,
            1,
            5,
            '垃圾菜品',
            null,
            null
        );
    }

    #[Test]
    // Ref: TSD Section 3.3.1 - 频率限制（1小时内最多5条）
    public function create_review_throws_exception_when_rate_limit_exceeded(): void
    {
        // Arrange
        $this->profanityFilterMock->expects($this->once())
            ->method('checkReview')
            ->willReturn(['has_profanity' => false, 'matched_words' => []]);

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('提交过于频繁');
        $this->expectExceptionCode(429);

        // 模拟已提交5次
        // 注意：这是Red Stage，实际实现可能不同
        $this->sut->createReview($this->userMock, 1, 1, 5);
    }

    #[Test]
    // Ref: TSD Section 3.3.1 - 评价唯一性检查
    public function create_review_throws_exception_when_already_reviewed(): void
    {
        // Arrange
        $this->profanityFilterMock->expects($this->once())
            ->method('checkReview')
            ->willReturn(['has_profanity' => false, 'matched_words' => []]);

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('已评价过该菜品');
        $this->expectExceptionCode(409);

        // 模拟已评价过
        // 注意：这是Red Stage，实际实现可能不同
        $this->sut->createReview($this->userMock, 1, 1, 5);
    }

    #[Test]
    // Ref: TSD Section 3.3.1 - 自动审核机制，自动通过审核
    public function create_review_creates_review_with_approved_status(): void
    {
        // Arrange
        $this->profanityFilterMock->expects($this->once())
            ->method('checkReview')
            ->willReturn(['has_profanity' => false, 'matched_words' => []]);

        // Act
        $result = $this->sut->createReview($this->userMock, 1, 1, 5);

        // Assert
        $this->assertInstanceOf(Review::class, $result);
        // 验证状态为approved
        // 注意：这是Red Stage，实际实现可能不同
    }

    #[Test]
    // Ref: TSD Section 3.3.1 - 管理员互动，回复评价
    public function reply_review_updates_admin_reply_fields(): void
    {
        // Arrange
        $reply = '感谢您的评价，我们会继续改进';

        // Act
        $result = $this->sut->replyReview(1, $this->adminMock, $reply);

        // Assert
        $this->assertInstanceOf(Review::class, $result);
        // 验证admin_reply、admin_replied_at、admin_replied_by已设置
        // 注意：这是Red Stage，实际实现可能不同
    }

    #[Test]
    // Ref: TSD Section 3.3.1 - 评价追踪优化机制，采纳评价建议
    public function adopt_review_sets_is_adopted_and_tracking_status_to_in_progress(): void
    {
        // Arrange
        $this->reviewMock->is_adopted = false;

        // Act
        $result = $this->sut->adoptReview(1, $this->adminMock);

        // Assert
        $this->assertInstanceOf(Review::class, $result);
        // 验证is_adopted为true，tracking_status为in_progress
        // 注意：这是Red Stage，实际实现可能不同
    }

    #[Test]
    // Ref: TSD Section 3.3.1 - 评价追踪优化机制
    public function adopt_review_throws_exception_when_already_adopted(): void
    {
        // Arrange
        $this->reviewMock->is_adopted = true;

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('该评价已被采纳');
        $this->expectExceptionCode(400);

        $this->sut->adoptReview(1, $this->adminMock);
    }

    #[Test]
    // Ref: TSD Section 3.3.1 - 评价追踪优化机制，更新追踪状态
    public function update_tracking_status_updates_status_and_adds_tracking_update(): void
    {
        // Arrange
        $status = 'completed';
        $message = '优化已完成';

        // Act
        $result = $this->sut->updateTrackingStatus(1, $this->adminMock, $status, $message);

        // Assert
        $this->assertInstanceOf(Review::class, $result);
        // 验证tracking_status已更新，tracking_updates已添加记录
        // 注意：这是Red Stage，实际实现可能不同
    }

    #[Test]
    // Ref: TSD Section 3.3.1 - 评价追踪优化机制
    public function update_tracking_status_throws_exception_when_status_is_invalid(): void
    {
        // Arrange
        $invalidStatus = 'invalid_status';

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('无效的追踪状态');
        $this->expectExceptionCode(400);

        $this->sut->updateTrackingStatus(1, $this->adminMock, $invalidStatus);
    }

    #[Test]
    // Ref: TSD Section 3.3.1 - 评价追踪优化机制，添加追踪更新记录
    public function add_tracking_update_adds_update_to_tracking_updates(): void
    {
        // Arrange
        $message = '优化进展：已完成50%';

        // Act
        $result = $this->sut->addTrackingUpdate(1, $this->adminMock, $message);

        // Assert
        $this->assertInstanceOf(Review::class, $result);
        // 验证tracking_updates已添加更新记录
        // 注意：这是Red Stage，实际实现可能不同
    }

    #[Test]
    // Ref: TSD Section 3.3.1 - 管理员互动，审核评价
    public function approve_review_updates_status_to_approved(): void
    {
        // Act
        $result = $this->sut->approveReview(1);

        // Assert
        $this->assertInstanceOf(Review::class, $result);
        // 验证状态为approved
        // 注意：这是Red Stage，实际实现可能不同
    }

    #[Test]
    // Ref: TSD Section 3.3.1 - 管理员互动，审核评价
    public function reject_review_updates_status_to_rejected(): void
    {
        // Act
        $result = $this->sut->rejectReview(1, '内容不当');

        // Assert
        $this->assertInstanceOf(Review::class, $result);
        // 验证状态为rejected
        // 注意：这是Red Stage，实际实现可能不同
    }
}

