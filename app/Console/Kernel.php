<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Models\Configuration;

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

        // 检查超时占用的桌位（执行频率从配置读取，默认1分钟）
        $checkInterval = (int) Configuration::getValue('table_occupation_release_check_interval_minutes', 1);
        $tableReleaseSchedule = $schedule->command('tables:release-expired-occupations')
            ->withoutOverlapping()
            ->runInBackground();
        
        // 根据配置的执行间隔设置执行频率
        if ($checkInterval <= 1) {
            $tableReleaseSchedule->everyMinute();
        } elseif ($checkInterval <= 5) {
            $tableReleaseSchedule->everyFiveMinutes();
        } elseif ($checkInterval <= 10) {
            $tableReleaseSchedule->everyTenMinutes();
        } elseif ($checkInterval <= 30) {
            $tableReleaseSchedule->everyThirtyMinutes();
        } else {
            $tableReleaseSchedule->hourly();
        }

        // 每5分钟检查一次超时的叫号排队（超时时间从配置读取，默认15分钟）
        $schedule->command('queues:check-expired')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->runInBackground();

        // 每天早上9点发送生日提醒（提前7天）
        $schedule->command('birthday:send-reminders --days=7')
            ->dailyAt('09:00')
            ->withoutOverlapping()
            ->runInBackground();

        // 每天晚上8点检查是否需要发送会员日提醒（会员日前1天）
        $schedule->command('member-day:send-reminders')
            ->dailyAt('20:00')
            ->withoutOverlapping()
            ->runInBackground();
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
