<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Configuration;
use App\Models\Reservation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckExpiredReservations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reservations:check-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '检查并处理过期的预约';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('开始检查过期预约...');

        // 获取超时时间配置（分钟）
        $timeoutMinutes = (int) Configuration::getValue('reservation_timeout_minutes', 30);

        // 查找需要处理的预约
        // 1. 状态为 confirmed 且超过预约时间 + 超时时间的预约
        $expiredReservations = Reservation::whereIn('status', ['pending', 'confirmed'])
            ->where('deposit_status', 'paid') // 只处理已支付定金的预约
            ->get()
            ->filter(function ($reservation) use ($timeoutMinutes) {
                if (!$reservation->date || !$reservation->time_slot) {
                    return false;
                }

                // 计算预约时间
                // 确保 date 只取日期部分（YYYY-MM-DD），time_slot 只取时间部分（HH:MM 或 HH:MM:SS）
                $dateStr = $reservation->date instanceof \Carbon\Carbon 
                    ? $reservation->date->format('Y-m-d') 
                    : (is_string($reservation->date) ? explode(' ', $reservation->date)[0] : $reservation->date);
                
                // 移除 time_slot 中可能包含的日期部分，只保留时间部分
                $timeStr = is_string($reservation->time_slot) 
                    ? trim(explode(' ', $reservation->time_slot)[count(explode(' ', $reservation->time_slot)) - 1])
                    : $reservation->time_slot;
                
                // 确保时间格式正确（最多8个字符：HH:MM:SS）
                if (strlen($timeStr) > 8) {
                    $timeStr = substr($timeStr, 0, 8);
                }
                
                $reservationDateTime = \Carbon\Carbon::parse($dateStr . ' ' . $timeStr);
                // 计算过期时间（预约时间 + 超时时间）
                $expiredTime = $reservationDateTime->copy()->addMinutes($timeoutMinutes);

                // 如果当前时间已超过过期时间
                return now()->greaterThan($expiredTime);
            });

        $count = 0;
        foreach ($expiredReservations as $reservation) {
            try {
                DB::transaction(function () use ($reservation) {
                    // 更新预约状态为过期
                    $reservation->update([
                        'status' => 'expired',
                    ]);

                    // 如果定金已支付但未退还，则没收定金
                    if ($reservation->deposit_status === 'paid' && !$reservation->deposit_refunded_at) {
                        $reservation->update([
                            'deposit_status' => 'forfeited',
                        ]);
                    }

                    // 释放桌位
                    if ($reservation->table) {
                        $reservation->table->update(['status' => 'available']);
                    }

                    Log::info('预约已过期并处理', [
                        'reservation_id' => $reservation->id,
                        'reservation_code' => $reservation->reservation_code,
                        'deposit_status' => $reservation->deposit_status,
                    ]);
                });

                $count++;
            } catch (\Exception $e) {
                Log::error('处理过期预约失败', [
                    'reservation_id' => $reservation->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        $this->info("共处理 {$count} 个过期预约");

        return Command::SUCCESS;
    }
}
