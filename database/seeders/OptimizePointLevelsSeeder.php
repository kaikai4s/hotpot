<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\PointLevel;
use Illuminate\Database\Seeder;

class OptimizePointLevelsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * 优化段位配置：
     * - 合理安排积分阶梯和折扣
     * - 添加详细的达到说明（需要几次订单、几次评价）
     * 
     * 积分获取规则：
     * - 订单：1元 = 1积分（基础），不同段位有倍数加成
     * - 评价：基础50积分，带图+20，首次+30
     * - 假设平均订单金额：200元
     */
    public function run(): void
    {
        // 计算辅助函数：根据积分计算需要的订单和评价次数
        $calculateRequirements = function (int $minPoints, float $multiplier = 1.0) {
            $avgOrderAmount = 200; // 平均订单金额
            $baseReviewPoints = 50; // 基础评价积分
            $avgReviewPoints = 60; // 平均评价积分（考虑带图、首次等）
            
            // 计算需要的订单次数（考虑倍数）
            $ordersNeeded = ceil($minPoints / ($avgOrderAmount * $multiplier));
            
            // 计算需要的评价次数（考虑倍数）
            $reviewsNeeded = ceil($minPoints / ($avgReviewPoints * $multiplier));
            
            // 混合方式：70%订单 + 30%评价
            $mixedOrders = ceil($minPoints * 0.7 / ($avgOrderAmount * $multiplier));
            $mixedReviews = ceil($minPoints * 0.3 / ($avgReviewPoints * $multiplier));
            
            return [
                'orders' => $ordersNeeded,
                'reviews' => $reviewsNeeded,
                'mixed_orders' => $mixedOrders,
                'mixed_reviews' => $mixedReviews,
            ];
        };

        $levels = [
            // 黑铁段位（1.0x倍数，无折扣）
            [
                'code' => 'heitie',
                'name' => '黑铁一',
                'min_points' => 0,
                'discount_type' => 'none',
                'discount_value' => 0,
                'max_discount_amount' => null,
                'min_order_amount' => 0,
                'sort_order' => 1,
                'description' => '初始段位，新用户注册即获得。开始您的积分之旅！',
            ],
            [
                'code' => 'heitie2',
                'name' => '黑铁二',
                'min_points' => 100,
                'discount_type' => 'percentage',
                'discount_value' => 1.00,
                'max_discount_amount' => null,
                'min_order_amount' => 50,
                'sort_order' => 2,
                'description' => '达到方式：约1次订单（200元）或2次评价可获得。权益：订单满50元享受1%折扣。',
            ],
            [
                'code' => 'heitie3',
                'name' => '黑铁三',
                'min_points' => 300,
                'discount_type' => 'percentage',
                'discount_value' => 2.00,
                'max_discount_amount' => null,
                'min_order_amount' => 50,
                'sort_order' => 3,
                'description' => '达到方式：约2次订单（400元）或5次评价可获得。权益：订单满50元享受2%折扣。',
            ],
            
            // 青铜段位（1.0x倍数）
            [
                'code' => 'qingtong1',
                'name' => '青铜一',
                'min_points' => 600,
                'discount_type' => 'percentage',
                'discount_value' => 3.00,
                'max_discount_amount' => null,
                'min_order_amount' => 50,
                'sort_order' => 4,
                'description' => '达到方式：约3次订单（600元）或10次评价可获得。权益：订单满50元享受3%折扣。',
            ],
            [
                'code' => 'qingtong2',
                'name' => '青铜二',
                'min_points' => 1000,
                'discount_type' => 'percentage',
                'discount_value' => 4.00,
                'max_discount_amount' => null,
                'min_order_amount' => 50,
                'sort_order' => 5,
                'description' => '达到方式：约5次订单（1000元）或17次评价可获得。权益：订单满50元享受4%折扣，可开始使用积分抵扣订单。',
            ],
            [
                'code' => 'qingtong3',
                'name' => '青铜三',
                'min_points' => 1500,
                'discount_type' => 'percentage',
                'discount_value' => 5.00,
                'max_discount_amount' => null,
                'min_order_amount' => 50,
                'sort_order' => 6,
                'description' => '达到方式：约8次订单（1600元）或25次评价可获得。权益：订单满50元享受5%折扣。',
            ],
            
            // 白银段位（1.2x倍数，积分获取速度提升20%）
            [
                'code' => 'baiyin1',
                'name' => '白银一',
                'min_points' => 2200,
                'discount_type' => 'percentage',
                'discount_value' => 6.00,
                'max_discount_amount' => null,
                'min_order_amount' => 50,
                'sort_order' => 7,
                'description' => '达到方式：约9次订单（1800元）或37次评价可获得。权益：订单满50元享受6%折扣，积分获取速度提升至1.2倍（消费和评价积分增加20%）。',
            ],
            [
                'code' => 'baiyin2',
                'name' => '白银二',
                'min_points' => 3000,
                'discount_type' => 'percentage',
                'discount_value' => 7.00,
                'max_discount_amount' => null,
                'min_order_amount' => 50,
                'sort_order' => 8,
                'description' => '达到方式：约13次订单（2600元）或50次评价可获得。权益：订单满50元享受7%折扣，积分获取速度1.2倍。',
            ],
            [
                'code' => 'baiyin3',
                'name' => '白银三',
                'min_points' => 4000,
                'discount_type' => 'percentage',
                'discount_value' => 8.00,
                'max_discount_amount' => null,
                'min_order_amount' => 50,
                'sort_order' => 9,
                'description' => '达到方式：约17次订单（3400元）或67次评价可获得。权益：订单满50元享受8%折扣，积分获取速度1.2倍。',
            ],
            
            // 黄金段位（1.5x倍数，积分获取速度提升50%）
            [
                'code' => 'huangjin1',
                'name' => '黄金一',
                'min_points' => 5500,
                'discount_type' => 'percentage',
                'discount_value' => 9.00,
                'max_discount_amount' => null,
                'min_order_amount' => 50,
                'sort_order' => 10,
                'description' => '达到方式：约18次订单（3600元）或92次评价可获得。权益：订单满50元享受9%折扣，积分获取速度提升至1.5倍（消费和评价积分增加50%）。',
            ],
            [
                'code' => 'huangjin2',
                'name' => '黄金二',
                'min_points' => 7500,
                'discount_type' => 'percentage',
                'discount_value' => 10.00,
                'max_discount_amount' => null,
                'min_order_amount' => 50,
                'sort_order' => 11,
                'description' => '达到方式：约25次订单（5000元）或125次评价可获得。权益：订单满50元享受10%折扣，积分获取速度1.5倍。',
            ],
            [
                'code' => 'huangjin3',
                'name' => '黄金三',
                'min_points' => 10000,
                'discount_type' => 'percentage',
                'discount_value' => 12.00,
                'max_discount_amount' => 50,
                'min_order_amount' => 50,
                'sort_order' => 12,
                'description' => '达到方式：约33次订单（6600元）或167次评价可获得。权益：订单满50元享受12%折扣（最高50元），积分获取速度1.5倍。',
            ],
            
            // 白金段位（2.0x倍数，积分获取速度提升100%）
            [
                'code' => 'baijin1',
                'name' => '白金一',
                'min_points' => 15000,
                'discount_type' => 'percentage',
                'discount_value' => 14.00,
                'max_discount_amount' => 60,
                'min_order_amount' => 50,
                'sort_order' => 13,
                'description' => '达到方式：约38次订单（7500元）或250次评价可获得。权益：订单满50元享受14%折扣（最高60元），积分获取速度提升至2.0倍（消费和评价积分翻倍）。',
            ],
            [
                'code' => 'baijin2',
                'name' => '白金二',
                'min_points' => 25000,
                'discount_type' => 'percentage',
                'discount_value' => 16.00,
                'max_discount_amount' => 80,
                'min_order_amount' => 50,
                'sort_order' => 14,
                'description' => '达到方式：约63次订单（12500元）或417次评价可获得。权益：订单满50元享受16%折扣（最高80元），积分获取速度2.0倍。',
            ],
            [
                'code' => 'baijin3',
                'name' => '白金三',
                'min_points' => 40000,
                'discount_type' => 'percentage',
                'discount_value' => 18.00,
                'max_discount_amount' => 100,
                'min_order_amount' => 50,
                'sort_order' => 15,
                'description' => '达到方式：约100次订单（20000元）或667次评价可获得。权益：订单满50元享受18%折扣（最高100元），积分获取速度2.0倍。',
            ],
            
            // 钻石段位（2.0x倍数）
            [
                'code' => 'zuanshi1',
                'name' => '钻石一',
                'min_points' => 55000,
                'discount_type' => 'percentage',
                'discount_value' => 20.00,
                'max_discount_amount' => 120,
                'min_order_amount' => 50,
                'sort_order' => 16,
                'description' => '达到方式：约138次订单（27500元）或917次评价可获得。权益：订单满50元享受20%折扣（最高120元），积分获取速度2.0倍，顶级VIP特权。',
            ],
            [
                'code' => 'zuanshi2',
                'name' => '钻石二',
                'min_points' => 70000,
                'discount_type' => 'percentage',
                'discount_value' => 22.00,
                'max_discount_amount' => 150,
                'min_order_amount' => 50,
                'sort_order' => 17,
                'description' => '达到方式：约175次订单（35000元）或1167次评价可获得。权益：订单满50元享受22%折扣（最高150元），积分获取速度2.0倍。',
            ],
            [
                'code' => 'zuanshi3',
                'name' => '钻石三',
                'min_points' => 85000,
                'discount_type' => 'percentage',
                'discount_value' => 24.00,
                'max_discount_amount' => 180,
                'min_order_amount' => 50,
                'sort_order' => 18,
                'description' => '达到方式：约213次订单（42500元）或1417次评价可获得。权益：订单满50元享受24%折扣（最高180元），积分获取速度2.0倍。',
            ],
            
            // 超凡段位（2.0x倍数）
            [
                'code' => 'chaofan1',
                'name' => '超凡一',
                'min_points' => 100000,
                'discount_type' => 'percentage',
                'discount_value' => 26.00,
                'max_discount_amount' => 200,
                'min_order_amount' => 50,
                'sort_order' => 19,
                'description' => '达到方式：约250次订单（50000元）或1667次评价可获得。权益：订单满50元享受26%折扣（最高200元），积分获取速度2.0倍，超级VIP特权。',
            ],
            [
                'code' => 'chaofan2',
                'name' => '超凡二',
                'min_points' => 120000,
                'discount_type' => 'percentage',
                'discount_value' => 28.00,
                'max_discount_amount' => 250,
                'min_order_amount' => 50,
                'sort_order' => 20,
                'description' => '达到方式：约300次订单（60000元）或2000次评价可获得。权益：订单满50元享受28%折扣（最高250元），积分获取速度2.0倍。',
            ],
            [
                'code' => 'chaofan3',
                'name' => '超凡三',
                'min_points' => 140000,
                'discount_type' => 'percentage',
                'discount_value' => 30.00,
                'max_discount_amount' => 300,
                'min_order_amount' => 50,
                'sort_order' => 21,
                'description' => '达到方式：约350次订单（70000元）或2333次评价可获得。权益：订单满50元享受30%折扣（最高300元），积分获取速度2.0倍。',
            ],
            
            // 神话段位（2.0x倍数）
            [
                'code' => 'shenhua1',
                'name' => '神话一',
                'min_points' => 160000,
                'discount_type' => 'percentage',
                'discount_value' => 32.00,
                'max_discount_amount' => 350,
                'min_order_amount' => 50,
                'sort_order' => 22,
                'description' => '达到方式：约400次订单（80000元）或2667次评价可获得。权益：订单满50元享受32%折扣（最高350元），积分获取速度2.0倍，至尊VIP特权。',
            ],
            [
                'code' => 'shenhua2',
                'name' => '神话二',
                'min_points' => 180000,
                'discount_type' => 'percentage',
                'discount_value' => 34.00,
                'max_discount_amount' => 400,
                'min_order_amount' => 50,
                'sort_order' => 23,
                'description' => '达到方式：约450次订单（90000元）或3000次评价可获得。权益：订单满50元享受34%折扣（最高400元），积分获取速度2.0倍。',
            ],
            [
                'code' => 'shenhua3',
                'name' => '神话三',
                'min_points' => 200000,
                'discount_type' => 'percentage',
                'discount_value' => 36.00,
                'max_discount_amount' => 450,
                'min_order_amount' => 50,
                'sort_order' => 24,
                'description' => '达到方式：约500次订单（100000元）或3333次评价可获得。权益：订单满50元享受36%折扣（最高450元），积分获取速度2.0倍。',
            ],
            
            // 赋能段位（2.0x倍数，最高段位）
            [
                'code' => 'funeng',
                'name' => '赋能',
                'min_points' => 250000,
                'discount_type' => 'percentage',
                'discount_value' => 40.00,
                'max_discount_amount' => 500,
                'min_order_amount' => 50,
                'sort_order' => 25,
                'description' => '达到方式：约625次订单（125000元）或4167次评价可获得。权益：订单满50元享受40%折扣（最高500元），积分获取速度2.0倍，最高段位，尊享所有特权！',
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

        $this->command->info('✅ 段位配置已优化！');
        $this->command->info('共配置 ' . count($levels) . ' 个段位');
        
        // 显示配置摘要
        $this->command->info("\n📊 段位配置摘要：");
        $this->command->table(
            ['段位名称', '最低积分', '折扣', '最大折扣', '最低订单'],
            array_map(function ($level) {
                return [
                    $level['name'],
                    number_format($level['min_points']),
                    $level['discount_type'] === 'none' ? '0%' : $level['discount_value'] . '%',
                    $level['max_discount_amount'] ? $level['max_discount_amount'] . '元' : '无限制',
                    $level['min_order_amount'] . '元',
                ];
            }, $levels)
        );
    }
}

