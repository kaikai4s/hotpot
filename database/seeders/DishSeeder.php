<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Dish;
use App\Models\DishCategory;
use Illuminate\Database\Seeder;

class DishSeeder extends Seeder
{
    public function run(): void
    {
        $categoryMap = [
            '锅底' => 1,
            '肉类' => 2,
            '海鲜' => 3,
            '蔬菜' => 4,
            '豆制品' => 5,
            '主食' => 6,
            '饮料' => 7,
        ];
        
        $dishes = [
            // 锅底
            ['name' => '麻辣锅底', 'description' => '经典麻辣口味', 'price' => 58.00, 'category_id' => $categoryMap['锅底']],
            ['name' => '清汤锅底', 'description' => '清淡鲜美', 'price' => 48.00, 'category_id' => $categoryMap['锅底']],
            ['name' => '番茄锅底', 'description' => '酸甜可口', 'price' => 52.00, 'category_id' => $categoryMap['锅底']],
            
            // 肉类
            ['name' => '精品肥牛', 'description' => '新鲜肥牛片', 'price' => 68.00, 'category_id' => $categoryMap['肉类']],
            ['name' => '羊肉卷', 'description' => '优质羊肉', 'price' => 58.00, 'category_id' => $categoryMap['肉类']],
            ['name' => '五花肉', 'description' => '肥瘦相间', 'price' => 48.00, 'category_id' => $categoryMap['肉类']],
            
            // 海鲜
            ['name' => '鲜虾', 'description' => '新鲜大虾', 'price' => 88.00, 'category_id' => $categoryMap['海鲜']],
            ['name' => '扇贝', 'description' => '新鲜扇贝', 'price' => 78.00, 'category_id' => $categoryMap['海鲜']],
            
            // 蔬菜
            ['name' => '生菜', 'description' => '新鲜生菜', 'price' => 12.00, 'category_id' => $categoryMap['蔬菜']],
            ['name' => '白菜', 'description' => '新鲜白菜', 'price' => 10.00, 'category_id' => $categoryMap['蔬菜']],
            
            // 主食
            ['name' => '手工面条', 'description' => 'Q弹有劲', 'price' => 15.00, 'category_id' => $categoryMap['主食']],
            ['name' => '米饭', 'description' => '香糯米饭', 'price' => 5.00, 'category_id' => $categoryMap['主食']],
            
            // 饮料
            ['name' => '可乐', 'description' => '冰镇可乐', 'price' => 8.00, 'category_id' => $categoryMap['饮料']],
            ['name' => '酸梅汤', 'description' => '解腻神器', 'price' => 12.00, 'category_id' => $categoryMap['饮料']],
        ];
        
        foreach ($dishes as $dish) {
            Dish::firstOrCreate(
                ['name' => $dish['name']],
                $dish
            );
        }
    }
}

