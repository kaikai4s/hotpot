<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\DishCategory;
use Illuminate\Database\Seeder;

class DishCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => '锅底', 'description' => '各种火锅底料', 'sort_order' => 1],
            ['name' => '肉类', 'description' => '新鲜肉类', 'sort_order' => 2],
            ['name' => '海鲜', 'description' => '新鲜海鲜', 'sort_order' => 3],
            ['name' => '蔬菜', 'description' => '新鲜蔬菜', 'sort_order' => 4],
            ['name' => '豆制品', 'description' => '豆腐、豆皮等', 'sort_order' => 5],
            ['name' => '主食', 'description' => '面条、米饭等', 'sort_order' => 6],
            ['name' => '饮料', 'description' => '各种饮品', 'sort_order' => 7],
        ];

        foreach ($categories as $category) {
            DishCategory::firstOrCreate(
                ['name' => $category['name']],
                $category
            );
        }
    }
}

