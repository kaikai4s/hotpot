<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\PointLevel;
use Illuminate\Database\Seeder;

class UpdatePointLevelsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * 根据积分获取规则和段位折扣，合理设置每个段位所需的积分
     */
    public function run(): void
    {
        $levels = [
            // 黑铁段位
            [
                'code' => 'heitie',
                'name' => '黑铁一',
                'min_points' => 0,
                'discount_type' => 'none',
                'discount_value' => 0,
                'max_discount_amount' => null,
                'min_order_amount' => 0,
                'sort_order' => 1,
                'description' => '初始段位，无折扣优惠',
            ],
            [
                'code' => 'heitie2',
                'name' => '黑铁二',
                'min_points' => 100,
                'discount_type' => 'percentage',
                'discount_value' => 1.00,
                'max_discount_amount' => null,
                'min_order_amount' => 0,
                'sort_order' => 2,
                'description' => '约1-2次订单或2次评价可获得',
            ],
            [
                'code' => 'heitie3',
                'name' => '黑铁三',
                'min_points' => 300,
                'discount_type' => 'percentage',
                'discount_value' => 2.00,
                'max_discount_amount' => null,
                'min_order_amount' => 0,
                'sort_order' => 3,
                'description' => '约3次订单或3-4次评价可获得',
            ],
            
            // 青铜段位
            [
                'code' => 'qingtong1',
                'name' => '青铜一',
                'min_points' => 600,
                'discount_type' => 'percentage',
                'discount_value' => 3.00,
                'max_discount_amount' => null,
                'min_order_amount' => 0,
                'sort_order' => 4,
                'description' => '约6次订单或6-8次评价可获得',
            ],
            [
                'code' => 'qingtong2',
                'name' => '青铜二',
                'min_points' => 1000,
                'discount_type' => 'percentage',
                'discount_value' => 4.00,
                'max_discount_amount' => null,
                'min_order_amount' => 0,
                'sort_order' => 5,
                'description' => '约10次订单，可开始使用积分抵扣',
            ],
            [
                'code' => 'qingtong3',
                'name' => '青铜三',
                'min_points' => 1500,
                'discount_type' => 'percentage',
                'discount_value' => 5.00,
                'max_discount_amount' => null,
                'min_order_amount' => 0,
                'sort_order' => 6,
                'description' => '约15次订单可获得',
            ],
            
            // 白银段位（1.2x积分倍数）
            [
                'code' => 'baiyin1',
                'name' => '白银一',
                'min_points' => 2200,
                'discount_type' => 'percentage',
                'discount_value' => 6.00,
                'max_discount_amount' => null,
                'min_order_amount' => 0,
                'sort_order' => 7,
                'description' => '约18次订单可获得，积分获取速度提升至1.2倍',
            ],
            [
                'code' => 'baiyin2',
                'name' => '白银二',
                'min_points' => 3000,
                'discount_type' => 'percentage',
                'discount_value' => 7.00,
                'max_discount_amount' => null,
                'min_order_amount' => 0,
                'sort_order' => 8,
                'description' => '约25次订单可获得',
            ],
            [
                'code' => 'baiyin3',
                'name' => '白银三',
                'min_points' => 4000,
                'discount_type' => 'percentage',
                'discount_value' => 8.00,
                'max_discount_amount' => null,
                'min_order_amount' => 0,
                'sort_order' => 9,
                'description' => '约33次订单可获得',
            ],
            
            // 黄金段位（1.5x积分倍数）
            [
                'code' => 'huangjin1',
                'name' => '黄金一',
                'min_points' => 5500,
                'discount_type' => 'percentage',
                'discount_value' => 9.00,
                'max_discount_amount' => null,
                'min_order_amount' => 0,
                'sort_order' => 10,
                'description' => '约37次订单可获得，积分获取速度提升至1.5倍',
            ],
            [
                'code' => 'huangjin2',
                'name' => '黄金二',
                'min_points' => 7500,
                'discount_type' => 'percentage',
                'discount_value' => 10.00,
                'max_discount_amount' => null,
                'min_order_amount' => 0,
                'sort_order' => 11,
                'description' => '约50次订单可获得',
            ],
            [
                'code' => 'huangjin3',
                'name' => '黄金三',
                'min_points' => 10000,
                'discount_type' => 'percentage',
                'discount_value' => 12.00,
                'max_discount_amount' => null,
                'min_order_amount' => 0,
                'sort_order' => 12,
                'description' => '约67次订单可获得',
            ],
            
            // 白金段位（2.0x积分倍数）
            [
                'code' => 'baijin1',
                'name' => '白金一',
                'min_points' => 15000,
                'discount_type' => 'percentage',
                'discount_value' => 15.00,
                'max_discount_amount' => null,
                'min_order_amount' => 0,
                'sort_order' => 13,
                'description' => '约75次订单可获得，积分获取速度提升至2.0倍',
            ],
            [
                'code' => 'baijin2',
                'name' => '白金二',
                'min_points' => 25000,
                'discount_type' => 'percentage',
                'discount_value' => 18.00,
                'max_discount_amount' => null,
                'min_order_amount' => 0,
                'sort_order' => 14,
                'description' => '约125次订单可获得',
            ],
            [
                'code' => 'baijin3',
                'name' => '白金三',
                'min_points' => 40000,
                'discount_type' => 'percentage',
                'discount_value' => 20.00,
                'max_discount_amount' => null,
                'min_order_amount' => 0,
                'sort_order' => 15,
                'description' => '约200次订单可获得，最高段位',
            ],
        ];

        foreach ($levels as $levelData) {
            PointLevel::updateOrCreate(
                ['code' => $levelData['code']],
                array_merge($levelData, [
                    'is_active' => true,
                ])
            );
        }

        $this->command->info('段位积分配置已更新！');
        $this->command->info('共配置 ' . count($levels) . ' 个段位');
    }
}

