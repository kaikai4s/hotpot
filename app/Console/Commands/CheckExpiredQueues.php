<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Configuration;
use App\Models\Queue;
use Illuminate\Console\Command;
use App\Helpers\LoggerHelper;

class CheckExpiredQueues extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queues:check-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '检查并处理超时的叫号排队';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('开始检查超时的叫号排队...');

        // 获取叫号预留时间配置（分钟）
        $timeoutMinutes = (int) Configuration::getValue('queue_called_timeout_minutes', 15);

        // 查找超时的叫号排队（状态为called且叫号时间超过配置的时间）
        $expiredQueues = Queue::where('status', 'called')
            ->whereNotNull('called_at')
            ->where('called_at', '<=', now()->subMinutes($timeoutMinutes))
            ->get();

        $count = 0;
        foreach ($expiredQueues as $queue) {
            // 取消超时的排队
            $queue->update([
                'status' => 'cancelled',
            ]);

            LoggerHelper::tableInfo('超时叫号排队已自动取消', [
                'queue_id' => $queue->id,
                'queue_number' => $queue->queue_number,
                'user_id' => $queue->user_id,
                'called_at' => $queue->called_at?->toDateTimeString(),
                'timeout_minutes' => $timeoutMinutes,
            ]);

            $count++;
        }

        if ($count > 0) {
            $this->info("已处理 {$count} 个超时的叫号排队");
        } else {
            $this->info('没有超时的叫号排队');
        }

        return Command::SUCCESS;
    }
}

