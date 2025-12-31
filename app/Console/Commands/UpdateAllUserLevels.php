<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\MemberPoint;
use App\Models\PointLevel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateAllUserLevels extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'points:update-all-levels';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '批量更新所有用户的段位（根据当前积分和段位配置）';

    /**
     * Execute the console command.
     * 
     * 重要：段位判断基于 total_points（总积分），而不是 available_points（可用积分）
     * 总积分是用户累计获得的所有积分，不会因为积分兑换而减少
     */
    public function handle(): int
    {
        $this->info('开始批量更新用户段位...');
        $this->info('注意：段位判断基于总积分（total_points），而不是可用积分（available_points）');

        $memberPoints = MemberPoint::all();
        $total = $memberPoints->count();
        $updated = 0;
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        foreach ($memberPoints as $memberPoint) {
            // 使用总积分来判断段位，而不是可用积分
            // 这样即使积分被兑换或过期，段位也不会降低
            $totalPoints = $memberPoint->total_points;
            
            // 获取对应的段位
            $newLevel = PointLevel::getLevelByPoints($totalPoints);
            $newLevelCode = $newLevel ? $newLevel->code : 'bronze';

            // 如果段位不同，则更新
            if ($memberPoint->level !== $newLevelCode) {
                $oldLevel = $memberPoint->level;
                $memberPoint->update(['level' => $newLevelCode]);
                $updated++;
                
                $this->line("\n用户 ID {$memberPoint->user_id}: {$oldLevel} -> {$newLevelCode} (总积分: {$totalPoints}, 可用积分: {$memberPoint->available_points})");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("完成！共更新 {$updated} 个用户的段位，共 {$total} 个用户。");

        return Command::SUCCESS;
    }
}

