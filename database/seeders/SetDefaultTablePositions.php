<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Table;
use App\Models\RestaurantArea;
use Illuminate\Database\Seeder;

class SetDefaultTablePositions extends Seeder
{
    public function run(): void
    {
        // 获取自定义区域配置
        $windowArea = RestaurantArea::where('type', 'window')->where('is_active', true)->first();
        $cornerArea = RestaurantArea::where('type', 'corner')->where('is_active', true)->first();
        $centerArea = RestaurantArea::where('type', 'center')->where('is_active', true)->first();

        // 窗边区域边界（A开头）
        $windowX = $windowArea && isset($windowArea->boundaries['x']) ? $windowArea->boundaries['x'] : 0;
        $windowWidth = $windowArea && isset($windowArea->boundaries['width']) ? $windowArea->boundaries['width'] : 200;
        $windowCenterX = $windowX + $windowWidth / 2;

        // 角落区域边界（B开头）
        $cornerX = $cornerArea && isset($cornerArea->boundaries['x']) ? $cornerArea->boundaries['x'] : 600;
        $cornerWidth = $cornerArea && isset($cornerArea->boundaries['width']) ? $cornerArea->boundaries['width'] : 200;
        $cornerCenterX = $cornerX + $cornerWidth / 2;

        // 中央区域边界（C开头和测试位置）
        $centerX = $centerArea && isset($centerArea->boundaries['x']) ? $centerArea->boundaries['x'] : 200;
        $centerWidth = $centerArea && isset($centerArea->boundaries['width']) ? $centerArea->boundaries['width'] : 400;
        $centerY = $centerArea && isset($centerArea->boundaries['y']) ? $centerArea->boundaries['y'] : 0;
        $centerHeight = $centerArea && isset($centerArea->boundaries['height']) ? $centerArea->boundaries['height'] : 500;

        // A开头的桌位 - 窗边区域（左侧，X=100，垂直排列）
        $aTables = [
            ['name' => 'A01', 'x' => (int)$windowCenterX, 'y' => 100],
            ['name' => 'A02', 'x' => (int)$windowCenterX, 'y' => 170],
            ['name' => 'A03', 'x' => (int)$windowCenterX, 'y' => 240],
            ['name' => 'A04', 'x' => (int)$windowCenterX, 'y' => 310],
            ['name' => 'A05', 'x' => (int)$windowCenterX, 'y' => 380],
        ];

        // B开头的桌位 - 角落区域（右侧，X=700，垂直排列）
        $bTables = [
            ['name' => 'B01', 'x' => (int)$cornerCenterX, 'y' => 100],
            ['name' => 'B02', 'x' => (int)$cornerCenterX, 'y' => 200],
            ['name' => 'B03', 'x' => (int)$cornerCenterX, 'y' => 300],
            ['name' => 'B04', 'x' => (int)$cornerCenterX, 'y' => 400],
            ['name' => 'B05', 'x' => (int)$cornerCenterX, 'y' => 450],
        ];

        // C开头的桌位 - 中央区域（中间，X=200-600，网格排列）
        // 在中央区域中网格排列（3行4列）
        $cTables = [
            ['name' => 'C01', 'x' => (int)($centerX + $centerWidth * 0.2), 'y' => 100],
            ['name' => 'C02', 'x' => (int)($centerX + $centerWidth * 0.4), 'y' => 100],
            ['name' => 'C03', 'x' => (int)($centerX + $centerWidth * 0.6), 'y' => 100],
            ['name' => 'C04', 'x' => (int)($centerX + $centerWidth * 0.8), 'y' => 100],
            ['name' => 'C05', 'x' => (int)($centerX + $centerWidth * 0.2), 'y' => 250],
            ['name' => 'C06', 'x' => (int)($centerX + $centerWidth * 0.4), 'y' => 250],
            ['name' => 'C07', 'x' => (int)($centerX + $centerWidth * 0.6), 'y' => 250],
            ['name' => 'C08', 'x' => (int)($centerX + $centerWidth * 0.8), 'y' => 250],
            ['name' => 'C09', 'x' => (int)($centerX + $centerWidth * 0.2), 'y' => 400],
            ['name' => 'C10', 'x' => (int)($centerX + $centerWidth * 0.4), 'y' => 400],
        ];

        // 测试位置 - 中央区域（中间）
        $testTable = [
            ['name' => '测试位置', 'x' => (int)($centerX + $centerWidth * 0.6), 'y' => 400],
        ];

        // 更新A开头的桌位
        foreach ($aTables as $tableData) {
            $table = Table::where('name', $tableData['name'])->first();
            if ($table) {
                $table->update([
                    'default_position_x' => $tableData['x'],
                    'default_position_y' => $tableData['y'],
                ]);
                $this->command->info("已设置 {$tableData['name']} 的默认位置: ({$tableData['x']}, {$tableData['y']}) - 窗边区域");
            }
        }

        // 更新B开头的桌位
        foreach ($bTables as $tableData) {
            $table = Table::where('name', $tableData['name'])->first();
            if ($table) {
                $table->update([
                    'default_position_x' => $tableData['x'],
                    'default_position_y' => $tableData['y'],
                ]);
                $this->command->info("已设置 {$tableData['name']} 的默认位置: ({$tableData['x']}, {$tableData['y']}) - 角落区域");
            }
        }

        // 更新C开头的桌位
        foreach ($cTables as $tableData) {
            $table = Table::where('name', $tableData['name'])->first();
            if ($table) {
                $table->update([
                    'default_position_x' => $tableData['x'],
                    'default_position_y' => $tableData['y'],
                ]);
                $this->command->info("已设置 {$tableData['name']} 的默认位置: ({$tableData['x']}, {$tableData['y']}) - 中央区域");
            }
        }

        // 更新测试位置
        foreach ($testTable as $tableData) {
            $table = Table::where('name', $tableData['name'])->first();
            if ($table) {
                $table->update([
                    'default_position_x' => $tableData['x'],
                    'default_position_y' => $tableData['y'],
                ]);
                $this->command->info("已设置 {$tableData['name']} 的默认位置: ({$tableData['x']}, {$tableData['y']}) - 中央区域");
            }
        }

        $this->command->info('所有桌位的默认位置已根据自定义区域重新设置完成！');
        $this->command->info('布局说明：');
        $this->command->info('  - A开头：窗边区域（左侧，X=0-200）');
        $this->command->info('  - B开头：角落区域（右侧，X=600-800）');
        $this->command->info('  - C开头：中央区域（中间，X=200-600）');
        $this->command->info('  - 测试位置：中央区域（中间，X=200-600）');
    }
}
