<?php

/**
 * 修复重复分类数据的 Seeder
 * 
 * 功能：
 * 1. 删除重复的分类（保留每个名称的第一条记录）
 * 2. 将菜品的 category_id 更新为保留的分类ID
 */

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FixDuplicateCategoriesSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('开始修复重复分类数据...');
        
        // 1. 查找所有重复的分类
        $duplicates = DB::table('dish_categories')
            ->select('name', DB::raw('COUNT(*) as count'), DB::raw('MIN(id) as keep_id'))
            ->groupBy('name')
            ->having('count', '>', 1)
            ->get();

        if ($duplicates->isEmpty()) {
            $this->command->info('没有发现重复的分类数据。');
            return;
        }

        $this->command->info("发现 {$duplicates->count()} 个重复的分类名称");

        DB::beginTransaction();
        
        try {
            foreach ($duplicates as $duplicate) {
                $this->command->info("处理分类: {$duplicate->name} (保留ID: {$duplicate->keep_id})");
                
                // 获取所有重复的分类ID（除了要保留的）
                $duplicateIds = DB::table('dish_categories')
                    ->where('name', $duplicate->name)
                    ->where('id', '!=', $duplicate->keep_id)
                    ->pluck('id')
                    ->toArray();

                $this->command->info("  - 需要删除的分类ID: " . implode(', ', $duplicateIds));

                // 2. 将使用重复分类ID的菜品更新为保留的分类ID
                $affectedDishes = DB::table('dishes')
                    ->whereIn('category_id', $duplicateIds)
                    ->update(['category_id' => $duplicate->keep_id]);

                $this->command->info("  - 更新了 {$affectedDishes} 个菜品的分类ID");

                // 3. 删除重复的分类
                $deletedCount = DB::table('dish_categories')
                    ->whereIn('id', $duplicateIds)
                    ->delete();

                $this->command->info("  - 删除了 {$deletedCount} 个重复分类");
            }

            DB::commit();
            $this->command->info('✅ 修复完成！');
            
            // 显示修复后的分类列表
            $categories = DB::table('dish_categories')->orderBy('sort_order')->get();
            $this->command->info("\n当前分类列表:");
            foreach ($categories as $category) {
                $dishCount = DB::table('dishes')->where('category_id', $category->id)->count();
                $this->command->info("  - ID:{$category->id} {$category->name} (菜品数: {$dishCount})");
            }
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('修复失败: ' . $e->getMessage());
            Log::error('修复重复分类失败', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
}
