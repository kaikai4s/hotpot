<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\PointRule;
use Illuminate\Database\Seeder;

class PointRuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rules = [
            [
                'rule_key' => 'order_earn',
                'rule_name' => '订单完成获得积分',
                'rule_type' => 'earn',
                'config' => [
                    'source' => 'order',
                    'base_ratio' => 1.0, // 1元 = 1积分
                    'level_multiplier' => [
                        // 黑铁段位（1.0x）
                        'heitie' => 1.0,
                        'heitie2' => 1.0,
                        'heitie3' => 1.0,
                        // 青铜段位（1.0x）
                        'qingtong1' => 1.0,
                        'qingtong2' => 1.0,
                        'qingtong3' => 1.0,
                        // 白银段位（1.2x）
                        'baiyin1' => 1.2,
                        'baiyin2' => 1.2,
                        'baiyin3' => 1.2,
                        // 黄金段位（1.5x）
                        'huangjin1' => 1.5,
                        'huangjin2' => 1.5,
                        'huangjin3' => 1.5,
                        // 白金段位（2.0x）
                        'baijin1' => 2.0,
                        'baijin2' => 2.0,
                        'baijin3' => 2.0,
                        // 钻石段位（2.0x）
                        'zuanshi1' => 2.0,
                        'zuanshi2' => 2.0,
                        'zuanshi3' => 2.0,
                        // 超凡段位（2.0x）
                        'chaofan1' => 2.0,
                        'chaofan2' => 2.0,
                        'chaofan3' => 2.0,
                        // 神话段位（2.0x）
                        'shenhua1' => 2.0,
                        'shenhua2' => 2.0,
                        'shenhua3' => 2.0,
                        // 赋能段位（2.0x）
                        'funeng' => 2.0,
                        // 兼容旧代码
                        'bronze' => 1.0,
                        'silver' => 1.2,
                        'gold' => 1.5,
                        'platinum' => 2.0,
                    ],
                    'min_amount' => 0,
                    'max_points_per_order' => null,
                ],
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'rule_key' => 'review_earn',
                'rule_name' => '评价获得积分',
                'rule_type' => 'earn',
                'config' => [
                    'source' => 'review',
                    'base_points' => 50, // 基础评价积分
                    'with_image_bonus' => 20, // 带图评价额外积分
                    'first_review_bonus' => 30, // 首次评价额外积分
                ],
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'rule_key' => 'point_use',
                'rule_name' => '积分使用规则',
                'rule_type' => 'use',
                'config' => [
                    'use_ratio' => 100, // 100积分 = 1元
                    'min_points' => 1000, // 最低使用1000积分
                    'max_percent' => 50, // 最多抵扣订单50%
                ],
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'rule_key' => 'point_expire',
                'rule_name' => '积分过期规则',
                'rule_type' => 'expire',
                'config' => [
                    'expire_days' => 365, // 积分有效期365天
                ],
                'is_active' => true,
                'sort_order' => 4,
            ],
            [
                'rule_key' => 'review_adoption',
                'rule_name' => '评价采纳获得积分',
                'rule_type' => 'earn',
                'config' => [
                    'source' => 'review_adoption',
                    'base_points' => 200, // 评价被采纳奖励200积分
                ],
                'is_active' => true,
                'sort_order' => 5,
            ],
        ];

        foreach ($rules as $rule) {
            PointRule::updateOrCreate(
                ['rule_key' => $rule['rule_key']],
                $rule
            );
        }
    }
}

