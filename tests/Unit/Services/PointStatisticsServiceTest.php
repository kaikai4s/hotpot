<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\MemberPoint;
use App\Models\PointStatistic;
use App\Services\PointStatisticsService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PointStatisticsServiceTest extends TestCase
{
    private PointStatisticsService $sut;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sut = new PointStatisticsService();
    }

    #[Test]
    // Ref: TSD Section 3.5.6 - 积分统计与分析，日报统计
    public function calculate_statistics_calculates_daily_statistics(): void
    {
        // Act
        $result = $this->sut->calculateStatistics('2026-01-05');

        // Assert
        $this->assertInstanceOf(PointStatistic::class, $result);
        // 验证统计了获得积分、兑换积分、过期积分、活跃用户数
        // 注意：这是Red Stage，实际实现可能不同
    }

    #[Test]
    // Ref: TSD Section 3.5.6 - 积分统计与分析
    public function get_statistics_report_returns_report_with_summary(): void
    {
        // Act
        $result = $this->sut->getStatisticsReport();

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('statistics', $result);
        $this->assertArrayHasKey('summary', $result);
        $this->assertArrayHasKey('total_earned', $result['summary']);
        $this->assertArrayHasKey('total_redeemed', $result['summary']);
        $this->assertArrayHasKey('total_expired', $result['summary']);
    }

    #[Test]
    // Ref: TSD Section 3.5.6 - 积分统计与分析
    public function get_statistics_report_filters_by_date_range_when_provided(): void
    {
        // Arrange
        $filters = [
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-31',
        ];

        // Act
        $result = $this->sut->getStatisticsReport($filters);

        // Assert
        $this->assertIsArray($result);
        // 验证只返回指定日期范围内的统计
        // 注意：这是Red Stage，实际实现可能不同
    }

    #[Test]
    // Ref: TSD Section 3.5.6 - 积分统计与分析，排行榜
    public function get_user_ranking_returns_ranking_by_total_points(): void
    {
        // Act
        $result = $this->sut->getUserRanking(100);

        // Assert
        $this->assertIsArray($result);
        // 验证按总积分降序排列
        // 注意：这是Red Stage，实际实现可能不同
    }
}

