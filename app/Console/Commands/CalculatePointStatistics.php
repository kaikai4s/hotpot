<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\PointStatisticsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CalculatePointStatistics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'points:calculate-statistics {date?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '计算积分统计数据（默认计算昨天的数据）';

    /**
     * Execute the console command.
     */
    public function handle(PointStatisticsService $statisticsService): int
    {
        $date = $this->argument('date') ?? now()->subDay()->format('Y-m-d');

        $this->info("开始计算 {$date} 的积分统计数据...");

        try {
            $statistic = $statisticsService->calculateStatistics($date);
            
            $this->info("统计完成！");
            $this->table(
                ['项目', '数值'],
                [
                    ['日期', $statistic->stat_date],
                    ['获得积分总数', $statistic->total_earned],
                    ['兑换积分总数', $statistic->total_redeemed],
                    ['过期积分总数', $statistic->total_expired],
                    ['活跃用户数', $statistic->active_users],
                ]
            );

            Log::info('积分统计计算完成', [
                'date' => $date,
                'statistic' => $statistic->toArray(),
            ]);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('计算积分统计失败：' . $e->getMessage());
            Log::error('积分统计计算失败', [
                'date' => $date,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }
}

