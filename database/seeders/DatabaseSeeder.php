<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\AdminSeeder;
use Database\Seeders\ConfigurationSeeder;
use Database\Seeders\PointRuleSeeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            RoleSeeder::class,
            ConfigurationSeeder::class,
            PointRuleSeeder::class,
            DishCategorySeeder::class,
            TableSeeder::class,
            DishSeeder::class,
            AdminSeeder::class,
            OrderSeeder::class,
        ]);
    }
}

