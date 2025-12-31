<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Table;
use Illuminate\Database\Seeder;

class TableSeeder extends Seeder
{
    public function run(): void
    {
        // 创建20个桌位
        $tables = [];
        
        // 窗边桌位（A01-A05）
        for ($i = 1; $i <= 5; $i++) {
            $tables[] = [
                'name' => 'A' . str_pad((string)$i, 2, '0', STR_PAD_LEFT),
                'capacity' => 4,
                'type' => 'window',
                'position_x' => 10 + ($i - 1) * 50,
                'position_y' => 10,
                'status' => 'available',
            ];
        }
        
        // 角落桌位（B01-B05）
        for ($i = 1; $i <= 5; $i++) {
            $tables[] = [
                'name' => 'B' . str_pad((string)$i, 2, '0', STR_PAD_LEFT),
                'capacity' => 6,
                'type' => 'corner',
                'position_x' => 10 + ($i - 1) * 50,
                'position_y' => 100,
                'status' => 'available',
            ];
        }
        
        // 中央桌位（C01-C10）
        for ($i = 1; $i <= 10; $i++) {
            $tables[] = [
                'name' => 'C' . str_pad((string)$i, 2, '0', STR_PAD_LEFT),
                'capacity' => 4,
                'type' => 'center',
                'position_x' => 50 + (($i - 1) % 5) * 60,
                'position_y' => 200 + intval(($i - 1) / 5) * 60,
                'status' => 'available',
            ];
        }
        
        foreach ($tables as $table) {
            Table::firstOrCreate(
                ['name' => $table['name']],
                $table
            );
        }
    }
}

