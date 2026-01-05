<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Configuration;
use Illuminate\Database\Seeder;

class AddTableOccupationTimeoutConfigSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Configuration::updateOrCreate(
            ['key' => 'table_occupation_timeout_hours'],
            [
                'value' => '4',
                'type' => 'integer',
                'group' => 'reservation',
                'label' => '桌位占用超时时间（小时）',
                'description' => '桌位占用超过此时间且没有进行中的订单时，系统会自动释放桌位。默认4小时。',
                'sort_order' => 100,
                'is_public' => false,
            ]
        );
    }
}

