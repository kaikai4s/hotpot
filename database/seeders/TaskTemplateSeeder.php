<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\TaskTemplate;
use Illuminate\Database\Seeder;

class TaskTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = [
            // 每日任务
            [
                'name' => '每日签到',
                'description' => '完成每日签到，获得积分奖励',
                'type' => 'daily',
                'category' => 'sign',
                'target_value' => ['count' => 1],
                'reward_points' => 10,
                'reward_coupon_id' => null,
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => '评价菜品',
                'description' => '评价1道菜品，分享您的用餐体验',
                'type' => 'daily',
                'category' => 'review',
                'target_value' => ['count' => 1],
                'reward_points' => 30,
                'reward_coupon_id' => null,
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => '分享到朋友圈',
                'description' => '分享评价或订单到朋友圈，让更多朋友了解我们',
                'type' => 'daily',
                'category' => 'share',
                'target_value' => ['count' => 1],
                'reward_points' => 20,
                'reward_coupon_id' => null,
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'name' => '浏览菜品',
                'description' => '浏览5道菜品详情，发现更多美味',
                'type' => 'daily',
                'category' => 'browse',
                'target_value' => ['count' => 5],
                'reward_points' => 15,
                'reward_coupon_id' => null,
                'is_active' => true,
                'sort_order' => 4,
            ],

            // 每周任务
            [
                'name' => '消费达人',
                'description' => '本周完成3笔订单，成为消费达人',
                'type' => 'weekly',
                'category' => 'order',
                'target_value' => ['count' => 3],
                'reward_points' => 100,
                'reward_coupon_id' => null,
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => '评价达人',
                'description' => '本周评价5道菜品，分享您的用餐心得',
                'type' => 'weekly',
                'category' => 'review',
                'target_value' => ['count' => 5],
                'reward_points' => 150,
                'reward_coupon_id' => null,
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => '分享达人',
                'description' => '本周分享5次到朋友圈，让更多人了解我们',
                'type' => 'weekly',
                'category' => 'share',
                'target_value' => ['count' => 5],
                'reward_points' => 80,
                'reward_coupon_id' => null,
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'name' => '邀请达人',
                'description' => '本周邀请2位好友注册，获得丰厚奖励',
                'type' => 'weekly',
                'category' => 'invite',
                'target_value' => ['count' => 2],
                'reward_points' => 200,
                'reward_coupon_id' => null,
                'is_active' => true,
                'sort_order' => 4,
            ],

            // 成就任务（一次性）
            [
                'name' => '首次消费',
                'description' => '完成首次订单，开启积分之旅',
                'type' => 'achievement',
                'category' => 'order',
                'target_value' => ['count' => 1],
                'reward_points' => 50,
                'reward_coupon_id' => null,
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => '消费达人',
                'description' => '累计消费达到1000元，享受更多优惠',
                'type' => 'achievement',
                'category' => 'order',
                'target_value' => ['amount' => 1000],
                'reward_points' => 500,
                'reward_coupon_id' => null,
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => '评价达人',
                'description' => '累计评价10次，分享您的用餐体验',
                'type' => 'achievement',
                'category' => 'review',
                'target_value' => ['count' => 10],
                'reward_points' => 300,
                'reward_coupon_id' => null,
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'name' => '邀请达人',
                'description' => '邀请10位好友注册，获得丰厚奖励',
                'type' => 'achievement',
                'category' => 'invite',
                'target_value' => ['count' => 10],
                'reward_points' => 1000,
                'reward_coupon_id' => null,
                'is_active' => true,
                'sort_order' => 4,
            ],
        ];

        foreach ($templates as $templateData) {
            TaskTemplate::updateOrCreate(
                [
                    'name' => $templateData['name'],
                    'type' => $templateData['type'],
                ],
                $templateData
            );
        }

        $this->command->info('任务模板已创建！共 ' . count($templates) . ' 个任务模板');
    }
}

