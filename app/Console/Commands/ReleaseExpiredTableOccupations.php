<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Table;
use App\Models\Order;
use App\Models\Configuration;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ReleaseExpiredTableOccupations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tables:release-expired-occupations {--hours= : 超过多少小时未更新订单则释放桌位（可选，默认从配置读取）}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '释放超时占用的桌位（防止用户离开后忘记释放）';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // 优先使用命令行参数，否则从配置读取，默认4小时
        $hours = $this->option('hours') 
            ? (int) $this->option('hours') 
            : (int) Configuration::getValue('table_occupation_timeout_hours', 4);
        
        $expiredTime = Carbon::now()->subHours($hours);

        $this->info("开始检查超过 {$hours} 小时未更新的占用桌位...");

        // 查找状态为 occupied 且 occupied_at 超过指定时间的桌位
        $occupiedTables = Table::where('status', 'occupied')
            ->whereNotNull('occupied_at')
            ->where('occupied_at', '<', $expiredTime)
            ->get();

        $releasedCount = 0;

        foreach ($occupiedTables as $table) {
            // 检查该桌位是否有进行中的订单
            $hasActiveOrders = Order::where('table_id', $table->id)
                ->whereIn('status', ['pending', 'paid', 'pending_review'])
                ->exists();

            // 如果没有进行中的订单，释放桌位
            if (!$hasActiveOrders) {
                $table->update([
                    'status' => 'available',
                    'occupied_at' => null,
                ]);

                $releasedCount++;
                $this->info("已释放桌位: {$table->name} (占用时间: {$table->occupied_at})");

                Log::info('定时任务释放超时占用的桌位', [
                    'table_id' => $table->id,
                    'table_name' => $table->name,
                    'occupied_at' => $table->occupied_at,
                    'expired_hours' => $hours,
                ]);
            } else {
                $this->warn("桌位 {$table->name} 仍有进行中的订单，跳过释放");
            }
        }

        $this->info("完成！共释放 {$releasedCount} 个桌位");

        return Command::SUCCESS;
    }
}

