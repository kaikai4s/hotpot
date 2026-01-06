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
            ? (float) $this->option('hours') 
            : (float) Configuration::getValue('table_occupation_timeout_hours', 4);
        
        // 使用 subRealHours 支持小数小时（subHours 可能不支持小数）
        $expiredTime = Carbon::now()->subRealHours($hours);

        $this->info("开始检查超过 {$hours} 小时未更新的占用桌位...");

        // 查找状态为 occupied 且 occupied_at 超过指定时间的桌位
        $occupiedTables = Table::where('status', 'occupied')
            ->whereNotNull('occupied_at')
            ->where('occupied_at', '<', $expiredTime)
            ->get();

        $releasedCount = 0;

        foreach ($occupiedTables as $table) {
            // 检查该桌位是否有真正进行中的订单（pending_review 状态表示已完成，只是等待评价，不应该阻止释放）
            $hasActiveOrders = Order::where('table_id', $table->id)
                ->whereIn('status', ['pending', 'paid'])
                ->exists();

            // 检查是否有最近更新的订单（即使状态不是进行中，但最近更新过，说明可能还在使用）
            // 但 pending_review 和 completed 状态的订单不应该阻止释放
            $hasRecentOrders = Order::where('table_id', $table->id)
                ->where('updated_at', '>', $expiredTime)
                ->whereNotIn('status', ['pending_review', 'completed', 'cancelled'])
                ->exists();

            // 如果没有进行中的订单，且没有最近更新的订单，释放桌位
            if (!$hasActiveOrders && !$hasRecentOrders) {
                $table->update([
                    'status' => 'available',
                    'occupied_at' => null,
                    'occupied_by_user_id' => null,
                    'team_code' => null,
                ]);

                $releasedCount++;
                $occupiedAtStr = $table->occupied_at ? $table->occupied_at->toDateTimeString() : '未知';
                $this->info("已释放桌位: {$table->name} (占用时间: {$occupiedAtStr})");

                Log::info('定时任务释放超时占用的桌位', [
                    'table_id' => $table->id,
                    'table_name' => $table->name,
                    'occupied_at' => $occupiedAtStr,
                    'expired_hours' => $hours,
                ]);
            } else {
                $reason = $hasActiveOrders ? '有进行中的订单' : '有最近更新的订单';
                $this->warn("桌位 {$table->name} {$reason}，跳过释放");
            }
        }

        $this->info("完成！共释放 {$releasedCount} 个桌位");

        return Command::SUCCESS;
    }
}

