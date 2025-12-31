<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $superAdminRole = \App\Models\Role::where('name', 'super_admin')->first();
        $operatorRole = \App\Models\Role::where('name', 'operator')->first();

        $admin = Admin::firstOrCreate(
            ['username' => 'admin'],
            [
                'email' => 'admin@hotpot.com',
                'password' => Hash::make('admin123'),
                'name' => '系统管理员',
                'role' => 'super_admin',
                'is_active' => true,
            ]
        );
        
        if ($superAdminRole && !$admin->roles()->where('roles.id', $superAdminRole->id)->exists()) {
            $admin->roles()->attach($superAdminRole->id);
        }
        
        $operator = Admin::firstOrCreate(
            ['username' => 'operator'],
            [
                'email' => 'operator@hotpot.com',
                'password' => Hash::make('operator123'),
                'name' => '操作员',
                'role' => 'operator',
                'is_active' => true,
            ]
        );
        
        if ($operatorRole && !$operator->roles()->where('roles.id', $operatorRole->id)->exists()) {
            $operator->roles()->attach($operatorRole->id);
        }

        $this->command->info('管理员账号已初始化完成！');
        $this->command->info('  - 超级管理员: admin / admin123');
        $this->command->info('  - 操作员: operator / operator123');
    }
}

