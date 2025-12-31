<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        //
    ];

    protected function schedule(Schedule $schedule): void
    {
        // 每天凌晨2点处理过期积分
        $schedule->command('points:process-expirations')
            ->dailyAt('02:00')
            ->withoutOverlapping()
            ->runInBackground();

        // 每天凌晨1点计算前一天的积分统计
        $schedule->command('points:calculate-statistics')
            ->dailyAt('01:00')
            ->withoutOverlapping()
            ->runInBackground();

        // 每5分钟检查一次过期预约
        $schedule->command('reservations:check-expired')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->runInBackground();
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
