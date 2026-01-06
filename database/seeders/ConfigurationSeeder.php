<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Configuration;
use Illuminate\Database\Seeder;

class ConfigurationSeeder extends Seeder
{
    public function run(): void
    {
        $configs = [
            // 微信登录配置
            [
                'key' => 'wechat_login_mode',
                'value' => 'mock',
                'type' => 'string',
                'group' => 'wechat',
                'label' => '微信登录模式',
                'description' => 'mock: 模拟登录, real: 真实微信登录',
                'sort_order' => 1,
                'is_public' => true,
            ],
            [
                'key' => 'wechat_app_id',
                'value' => '',
                'type' => 'string',
                'group' => 'wechat',
                'label' => '微信AppID',
                'description' => '微信开放平台AppID',
                'sort_order' => 2,
                'is_public' => true,
            ],
            [
                'key' => 'wechat_app_secret',
                'value' => '',
                'type' => 'string',
                'group' => 'wechat',
                'label' => '微信AppSecret',
                'description' => '微信开放平台AppSecret（不公开）',
                'sort_order' => 3,
                'is_public' => false,
            ],
            // 手机号登录配置
            [
                'key' => 'phone_login_enabled',
                'value' => '0',
                'type' => 'boolean',
                'group' => 'auth',
                'label' => '启用手机号登录',
                'description' => '是否启用手机号验证码登录和手机号+密码登录功能',
                'sort_order' => 1,
                'is_public' => true,
            ],
            [
                'key' => 'phone_verification_code_expires_minutes',
                'value' => '5',
                'type' => 'integer',
                'group' => 'auth',
                'label' => '验证码有效期（分钟）',
                'description' => '手机验证码的有效期，默认5分钟',
                'sort_order' => 2,
                'is_public' => false,
            ],
            [
                'key' => 'sms_mode',
                'value' => 'mock',
                'type' => 'string',
                'group' => 'auth',
                'label' => '短信发送模式',
                'description' => 'mock: 模拟发送（开发环境）, real: 真实发送（生产环境）',
                'sort_order' => 3,
                'is_public' => false,
            ],
            [
                'key' => 'phone_verification_mock_enabled',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'auth',
                'label' => '启用模拟绑定手机号',
                'description' => '启用后，绑定手机号时不需要真实验证码，填写任意验证码即可绑定（仅用于开发测试）',
                'sort_order' => 4,
                'is_public' => false,
            ],
            // 网站配置
            [
                'key' => 'site_name',
                'value' => '火锅店',
                'type' => 'string',
                'group' => 'site',
                'label' => '网站名称',
                'description' => '网站显示名称',
                'sort_order' => 1,
                'is_public' => true,
            ],
            [
                'key' => 'site_description',
                'value' => '火锅店在线预约系统',
                'type' => 'string',
                'group' => 'site',
                'label' => '网站描述',
                'description' => '网站SEO描述',
                'sort_order' => 2,
                'is_public' => true,
            ],
            // 预约配置
            [
                'key' => 'reservation_deposit_enabled',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'reservation',
                'label' => '启用预约定金',
                'description' => '是否启用预约定金功能，启用后预约时需要支付定金',
                'sort_order' => 1,
                'is_public' => true,
            ],
            [
                'key' => 'reservation_deposit_amount',
                'value' => '50',
                'type' => 'integer',
                'group' => 'reservation',
                'label' => '预约定金金额',
                'description' => '预约时需要缴纳的定金金额（元）',
                'sort_order' => 2,
                'is_public' => true,
            ],
            [
                'key' => 'reservation_timeout_minutes',
                'value' => '30',
                'type' => 'integer',
                'group' => 'reservation',
                'label' => '预约超时时间（分钟）',
                'description' => '超过预约时间后多少分钟，定金不予退还',
                'sort_order' => 3,
                'is_public' => true,
            ],
            [
                'key' => 'reservation_cancel_hours_limit',
                'value' => '1',
                'type' => 'integer',
                'group' => 'reservation',
                'label' => '取消预约时间限制（小时）',
                'description' => '预约开始前多少小时内不可取消预约',
                'sort_order' => 4,
                'is_public' => true,
            ],
            // 排队配置
            [
                'key' => 'queue_called_timeout_minutes',
                'value' => '15',
                'type' => 'integer',
                'group' => 'queue',
                'label' => '叫号后预留时间（分钟）',
                'description' => '叫号后多少分钟内需要到店，超时后自动取消并重新排队',
                'sort_order' => 1,
                'is_public' => true,
            ],
            // 桌位配置
            [
                'key' => 'table_occupation_timeout_hours',
                'value' => '4',
                'type' => 'float',
                'group' => 'table',
                'label' => '桌位占用超时时间（小时）',
                'description' => '桌位占用超过此时间且没有进行中的订单时，系统会自动释放桌位。支持小数，例如0.01小时（36秒）。默认4小时。',
                'sort_order' => 1,
                'is_public' => true,
            ],
            [
                'key' => 'table_occupation_release_check_interval_minutes',
                'value' => '1',
                'type' => 'integer',
                'group' => 'table',
                'label' => '桌位释放检查间隔（分钟）',
                'description' => '系统每隔多少分钟检查一次超时占用的桌位。建议设置为超时时间的1/10到1/5，例如超时时间0.01小时（36秒）建议设置为1分钟。默认1分钟。',
                'sort_order' => 2,
                'is_public' => false,
            ],
            [
                'key' => 'points_to_money_rate',
                'value' => '100',
                'type' => 'integer',
                'group' => 'points',
                'label' => '积分抵扣比例',
                'description' => '多少积分可以抵扣1元（例如：100积分=1元）',
                'sort_order' => 1,
                'is_public' => true,
            ],
        ];

        foreach ($configs as $config) {
            Configuration::firstOrCreate(
                ['key' => $config['key']],
                $config
            );
        }

        $this->command->info('配置数据已初始化完成！');
    }
}
