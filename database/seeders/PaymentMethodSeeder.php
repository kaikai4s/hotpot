<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Configuration;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $configs = [
            [
                'key' => 'payment_method',
                'value' => 'mock',
                'type' => 'string',
                'group' => 'payment',
                'label' => '默认支付方式',
                'description' => 'mock: 模拟支付, wechat: 微信支付',
                'sort_order' => 1,
                'is_public' => true,
            ],
            [
                'key' => 'wechat_payment_enabled',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'payment',
                'label' => '启用微信支付',
                'description' => '是否启用微信支付功能',
                'sort_order' => 2,
                'is_public' => true,
            ],
            [
                'key' => 'mock_payment_enabled',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'payment',
                'label' => '启用模拟支付',
                'description' => '是否启用模拟支付功能（用于测试）',
                'sort_order' => 3,
                'is_public' => true,
            ],
        ];

        foreach ($configs as $config) {
            Configuration::firstOrCreate(
                ['key' => $config['key']],
                $config
            );
        }

        $this->command->info('支付方式配置已初始化完成！');
    }
}
