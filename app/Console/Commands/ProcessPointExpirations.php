<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\PointService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessPointExpirations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'points:process-expirations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '处理过期的积分';

    /**
     * Execute the console command.
     */
    public function handle(PointService $pointService): int
    {
        $this->info('开始处理过期积分...');

        try {
            $processed = $pointService->checkAndExpirePoints();
            
            $this->info("成功处理 {$processed} 条过期积分记录");
            Log::info('积分过期处理完成', ['processed' => $processed]);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('处理过期积分失败：' . $e->getMessage());
            Log::error('积分过期处理失败', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }
}

