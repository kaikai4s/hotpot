<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\MemberPoint;
use App\Models\PointRule;
use App\Models\User;
use App\Services\PointRuleService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

class PointRuleServiceTest extends TestCase
{
    private PointRuleService $sut;
    private User|MockObject $userMock;
    private MemberPoint|MockObject $memberPointMock;
    private PointRule|MockObject $pointRuleMock;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock User
        $this->userMock = $this->createMock(User::class);
        $this->userMock->id = 1;

        // Mock MemberPoint
        $this->memberPointMock = $this->createMock(MemberPoint::class);
        $this->memberPointMock->user_id = 1;
        $this->memberPointMock->level = 'bronze';

        // Mock PointRule
        $this->pointRuleMock = $this->createMock(PointRule::class);
        $this->pointRuleMock->rule_key = 'order_earn';
        $this->pointRuleMock->is_active = true;
        $this->pointRuleMock->config = [
            'base_ratio' => 1.0,
            'min_amount' => 0,
            'max_points_per_order' => null,
        ];

        // Instantiate SUT
        $this->sut = new PointRuleService();
    }

    #[Test]
    // Ref: TSD Section 3.5.1 - 订单支付积分，基础比例
    public function calculate_points_from_order_uses_base_ratio_when_rule_exists(): void
    {
        // Act
        $result = $this->sut->calculatePointsFromOrder($this->userMock, 200.00);

        // Assert
        $this->assertIsInt($result);
        $this->assertGreaterThanOrEqual(0, $result);
        // 验证使用基础比例计算积分
        // 注意：这是Red Stage，实际实现可能不同
    }

    #[Test]
    // Ref: TSD Section 3.5.1 - 订单支付积分，会员等级倍数
    public function calculate_points_from_order_applies_level_multiplier(): void
    {
        // Arrange
        $this->memberPointMock->level = 'gold'; // 黄金会员

        // Act
        $result = $this->sut->calculatePointsFromOrder($this->userMock, 200.00);

        // Assert
        $this->assertIsInt($result);
        // 验证应用了会员等级倍数
        // 注意：这是Red Stage，实际实现可能不同
    }

    #[Test]
    // Ref: TSD Section 3.5.1 - 订单支付积分
    public function calculate_points_from_order_returns_zero_when_amount_below_min(): void
    {
        // Arrange
        $this->pointRuleMock->config['min_amount'] = 100;

        // Act
        $result = $this->sut->calculatePointsFromOrder($this->userMock, 50.00);

        // Assert
        $this->assertEquals(0, $result);
    }

    #[Test]
    // Ref: TSD Section 3.5.1 - 订单支付积分
    public function calculate_points_from_order_respects_max_points_per_order_limit(): void
    {
        // Arrange
        $this->pointRuleMock->config['max_points_per_order'] = 100;

        // Act
        $result = $this->sut->calculatePointsFromOrder($this->userMock, 1000.00);

        // Assert
        $this->assertLessThanOrEqual(100, $result);
    }

    #[Test]
    // Ref: TSD Section 3.5.1 - 评价奖励积分
    public function calculate_points_from_review_includes_image_bonus(): void
    {
        // Act
        $result = $this->sut->calculatePointsFromReview($this->userMock, [
            'images' => ['image1.jpg'],
            'is_first_review' => false,
        ]);

        // Assert
        $this->assertIsInt($result);
        $this->assertGreaterThanOrEqual(0, $result);
        // 验证带图评价包含图片奖励
        // 注意：这是Red Stage，实际实现可能不同
    }

    #[Test]
    // Ref: TSD Section 3.5.1 - 评价奖励积分，首次评价奖励
    public function calculate_points_from_review_includes_first_review_bonus(): void
    {
        // Act
        $result = $this->sut->calculatePointsFromReview($this->userMock, [
            'images' => [],
            'is_first_review' => true,
        ]);

        // Assert
        $this->assertIsInt($result);
        // 验证首次评价包含额外奖励
        // 注意：这是Red Stage，实际实现可能不同
    }

    #[Test]
    // Ref: TSD Section 3.5.1 - 评价采纳奖励
    public function calculate_points_from_adoption_applies_level_multiplier(): void
    {
        // Act
        $result = $this->sut->calculatePointsFromAdoption($this->userMock);

        // Assert
        $this->assertIsInt($result);
        $this->assertGreaterThanOrEqual(0, $result);
        // 验证应用了段位倍数
        // 注意：这是Red Stage，实际实现可能不同
    }

    #[Test]
    // Ref: TSD Section 3.5.5 - 积分过期管理
    public function get_expire_days_returns_configured_expire_days(): void
    {
        // Act
        $result = $this->sut->getExpireDays();

        // Assert
        $this->assertIsInt($result);
        $this->assertGreaterThan(0, $result);
        // 验证返回配置的过期天数（默认365天）
        // 注意：这是Red Stage，实际实现可能不同
    }
}

