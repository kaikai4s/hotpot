<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\PointLevel;
use App\Models\PointRule;
use App\Services\PointRuleService;
use Illuminate\Console\Command;

class SyncPointLevelsToRule extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'points:sync-levels-to-rule';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '同步所有段位到积分规则配置的level_multiplier中';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('开始同步段位到积分规则配置...');

        // 获取订单积分规则
        $orderRule = PointRule::where('rule_key', 'order_earn')->where('is_active', true)->first();
        
        if (!$orderRule) {
            $this->error('未找到订单积分规则（order_earn），请先创建该规则');
            return 1;
        }

        // 获取所有启用的段位
        $levels = PointLevel::where('is_active', true)
            ->orderBy('sort_order', 'asc')
            ->orderBy('min_points', 'asc')
            ->get(['code', 'name', 'min_points']);

        if ($levels->isEmpty()) {
            $this->error('未找到任何启用的段位');
            return 1;
        }

        $this->info("找到 {$levels->count()} 个启用的段位");

        // 获取当前配置
        $config = $orderRule->config ?? [];
        $currentMultipliers = $config['level_multiplier'] ?? [];

        // 根据段位代码前缀确定倍数
        $multiplierMap = [];
        foreach ($levels as $level) {
            $code = $level->code;
            $multiplier = $this->determineMultiplier($code);
            $multiplierMap[$code] = $multiplier;
            
            $this->line("  {$code} ({$level->name}) -> {$multiplier}x");
        }

        // 合并现有配置（保留兼容旧代码的配置）
        $mergedMultipliers = array_merge($multiplierMap, $currentMultipliers);
        
        // 移除重复项，优先使用新配置
        $finalMultipliers = [];
        foreach ($mergedMultipliers as $code => $multiplier) {
            // 如果新配置中已有该段位，优先使用新配置
            if (isset($multiplierMap[$code])) {
                $finalMultipliers[$code] = $multiplierMap[$code];
            } else {
                // 保留旧配置（兼容性）
                $finalMultipliers[$code] = $multiplier;
            }
        }

        // 更新配置
        $config['level_multiplier'] = $finalMultipliers;
        $orderRule->config = $config;
        $orderRule->save();

        // 清除缓存
        $ruleService = app(PointRuleService::class);
        $ruleService->clearCache('order_earn');

        $this->info("\n同步完成！共同步 {$levels->count()} 个段位到积分规则配置");
        $this->info("配置中的段位倍数总数: " . count($finalMultipliers));
        
        return 0;
    }

    /**
     * 根据段位代码确定倍数
     */
    private function determineMultiplier(string $code): float
    {
        // 黑铁段位（1.0x）
        if (str_starts_with($code, 'heitie')) {
            return 1.0;
        }
        
        // 青铜段位（1.0x）
        if (str_starts_with($code, 'qingtong')) {
            return 1.0;
        }
        
        // 白银段位（1.2x）
        if (str_starts_with($code, 'baiyin')) {
            return 1.2;
        }
        
        // 黄金段位（1.5x）
        if (str_starts_with($code, 'huangjin')) {
            return 1.5;
        }
        
        // 白金及以上段位（2.0x）
        if (str_starts_with($code, 'baijin') || 
            str_starts_with($code, 'zuanshi') || 
            str_starts_with($code, 'chaofan') || 
            str_starts_with($code, 'shenhua') || 
            str_starts_with($code, 'funeng')) {
            return 2.0;
        }
        
        // 默认倍数
        return 1.0;
    }
}
