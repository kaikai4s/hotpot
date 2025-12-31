<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\PointLevel;
use Illuminate\Database\Seeder;

class UpdateAllPointLevelsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * 根据积分获取规则和段位折扣，合理设置所有段位所需的积分
     * 保留所有现有段位，只更新积分要求和折扣
     */
    public function run(): void
    {
        $levels = [
            // 黑铁段位（1.0x倍数）
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
                'description' => '约0.5次订单（200元）或2次评价可获得',
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
                'description' => '约1.5次订单或3次评价可获得',
            ],
            
            // 青铜段位（1.0x倍数）
            [
                'code' => 'qingtong1',
                'name' => '青铜一',
                'min_points' => 600,
                'discount_type' => 'percentage',
                'discount_value' => 3.00,
                'max_discount_amount' => null,
                'min_order_amount' => 0,
                'sort_order' => 4,
                'description' => '约3次订单或6次评价可获得',
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
                'description' => '约5次订单，可开始使用积分抵扣',
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
                'description' => '约7.5次订单或15次评价可获得',
            ],
            
            // 白银段位（1.2x倍数）
            [
                'code' => 'baiyin1',
                'name' => '白银一',
                'min_points' => 2200,
                'discount_type' => 'percentage',
                'discount_value' => 6.00,
                'max_discount_amount' => null,
                'min_order_amount' => 0,
                'sort_order' => 7,
                'description' => '约9次订单（从0开始），积分获取速度提升20%',
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
                'description' => '约12.5次订单（从0开始）',
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
                'description' => '约17次订单（从0开始）',
            ],
            
            // 黄金段位（1.5x倍数）
            [
                'code' => 'huangjin1',
                'name' => '黄金一',
                'min_points' => 5500,
                'discount_type' => 'percentage',
                'discount_value' => 9.00,
                'max_discount_amount' => null,
                'min_order_amount' => 0,
                'sort_order' => 10,
                'description' => '约23次订单（从0开始），积分获取速度提升50%',
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
                'description' => '约31次订单（从0开始）',
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
                'description' => '约42次订单（从0开始）',
            ],
            
            // 白金段位（2.0x倍数）
            [
                'code' => 'baijin1',
                'name' => '白金一',
                'min_points' => 15000,
                'discount_type' => 'percentage',
                'discount_value' => 14.00,
                'max_discount_amount' => null,
                'min_order_amount' => 0,
                'sort_order' => 13,
                'description' => '约38次订单（从0开始），积分获取速度提升100%',
            ],
            [
                'code' => 'baijin2',
                'name' => '白金二',
                'min_points' => 25000,
                'discount_type' => 'percentage',
                'discount_value' => 16.00,
                'max_discount_amount' => null,
                'min_order_amount' => 0,
                'sort_order' => 14,
                'description' => '约63次订单（从0开始）',
            ],
            [
                'code' => 'baijin3',
                'name' => '白金三',
                'min_points' => 40000,
                'discount_type' => 'percentage',
                'discount_value' => 18.00,
                'max_discount_amount' => null,
                'min_order_amount' => 0,
                'sort_order' => 15,
                'description' => '约100次订单（从0开始）',
            ],
            
            // 钻石段位（2.0x倍数）
            [
                'code' => 'zuanshi1',
                'name' => '钻石一',
                'min_points' => 55000,
                'discount_type' => 'percentage',
                'discount_value' => 20.00,
                'max_discount_amount' => null,
                'min_order_amount' => 0,
                'sort_order' => 16,
                'description' => '约138次订单（从0开始），顶级VIP',
            ],
            [
                'code' => 'zuanshi2',
                'name' => '钻石二',
                'min_points' => 70000,
                'discount_type' => 'percentage',
                'discount_value' => 22.00,
                'max_discount_amount' => null,
                'min_order_amount' => 0,
                'sort_order' => 17,
                'description' => '约175次订单（从0开始）',
            ],
            [
                'code' => 'zuanshi3',
                'name' => '钻石三',
                'min_points' => 85000,
                'discount_type' => 'percentage',
                'discount_value' => 24.00,
                'max_discount_amount' => null,
                'min_order_amount' => 0,
                'sort_order' => 18,
                'description' => '约213次订单（从0开始）',
            ],
            
            // 超凡段位（2.0x倍数）
            [
                'code' => 'chaofan1',
                'name' => '超凡一',
                'min_points' => 100000,
                'discount_type' => 'percentage',
                'discount_value' => 26.00,
                'max_discount_amount' => null,
                'min_order_amount' => 0,
                'sort_order' => 19,
                'description' => '约250次订单（从0开始），超级VIP',
            ],
            [
                'code' => 'chaofan2',
                'name' => '超凡二',
                'min_points' => 120000,
                'discount_type' => 'percentage',
                'discount_value' => 28.00,
                'max_discount_amount' => null,
                'min_order_amount' => 0,
                'sort_order' => 20,
                'description' => '约300次订单（从0开始）',
            ],
            [
                'code' => 'chaofan3',
                'name' => '超凡三',
                'min_points' => 140000,
                'discount_type' => 'percentage',
                'discount_value' => 30.00,
                'max_discount_amount' => null,
                'min_order_amount' => 0,
                'sort_order' => 21,
                'description' => '约350次订单（从0开始）',
            ],
            
            // 神话段位（2.0x倍数）
            [
                'code' => 'shenhua1',
                'name' => '神话一',
                'min_points' => 160000,
                'discount_type' => 'percentage',
                'discount_value' => 32.00,
                'max_discount_amount' => null,
                'min_order_amount' => 0,
                'sort_order' => 22,
                'description' => '约400次订单（从0开始），至尊VIP',
            ],
            [
                'code' => 'shenhua2',
                'name' => '神话二',
                'min_points' => 180000,
                'discount_type' => 'percentage',
                'discount_value' => 34.00,
                'max_discount_amount' => null,
                'min_order_amount' => 0,
                'sort_order' => 23,
                'description' => '约450次订单（从0开始）',
            ],
            [
                'code' => 'shenhua3',
                'name' => '神话三',
                'min_points' => 200000,
                'discount_type' => 'percentage',
                'discount_value' => 36.00,
                'max_discount_amount' => null,
                'min_order_amount' => 0,
                'sort_order' => 24,
                'description' => '约500次订单（从0开始）',
            ],
            
            // 赋能段位（2.0x倍数）
            [
                'code' => 'funeng',
                'name' => '赋能',
                'min_points' => 250000,
                'discount_type' => 'percentage',
                'discount_value' => 40.00,
                'max_discount_amount' => null,
                'min_order_amount' => 0,
                'sort_order' => 25,
                'description' => '约625次订单（从0开始），最高段位',
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

        $this->command->info('所有段位积分配置已更新！');
        $this->command->info('共配置 ' . count($levels) . ' 个段位');
        
        // 显示配置摘要
        $this->command->info("\n段位配置摘要：");
        $this->command->table(
            ['段位名称', '最低积分', '折扣', '描述'],
            array_map(function ($level) {
                return [
                    $level['name'],
                    $level['min_points'],
                    $level['discount_type'] === 'none' ? '0%' : $level['discount_value'] . '%',
                    $level['description'],
                ];
            }, $levels)
        );
    }
}

