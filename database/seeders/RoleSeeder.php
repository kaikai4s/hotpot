<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // 创建系统角色
        $superAdminRole = Role::firstOrCreate(
            ['name' => 'super_admin'],
            [
                'display_name' => '超级管理员',
                'description' => '拥有所有权限',
                'is_system' => true,
                'sort_order' => 1,
            ]
        );

        $adminRole = Role::firstOrCreate(
            ['name' => 'admin'],
            [
                'display_name' => '管理员',
                'description' => '拥有大部分管理权限',
                'is_system' => true,
                'sort_order' => 2,
            ]
        );

        $operatorRole = Role::firstOrCreate(
            ['name' => 'operator'],
            [
                'display_name' => '操作员',
                'description' => '拥有基本操作权限',
                'is_system' => true,
                'sort_order' => 3,
            ]
        );

        // 超级管理员拥有所有权限
        $allPermissions = Permission::all();
        $superAdminRole->permissions()->sync($allPermissions->pluck('id'));

        // 管理员拥有除角色、管理员管理和操作日志外的所有权限
        $adminPermissions = Permission::whereNotIn('group', ['roles', 'admins', 'audit_logs'])->get();
        $adminRole->permissions()->sync($adminPermissions->pluck('id'));

        // 操作员拥有查看和基本操作权限
        $operatorPermissions = Permission::whereIn('name', [
            'tables.view',
            'tables.position',
            'reservations.view',
            'reservations.update',
            'reviews.view',
            'reviews.approve',
            'reviews.reply',
            'reviews.adopt',
            'reviews.track',
            'areas.view',
            'points.view',
            'coupons.view',
        ])->get();
        $operatorRole->permissions()->sync($operatorPermissions->pluck('id'));

        $this->command->info('角色数据已初始化完成！');
        $this->command->info("  - 超级管理员: {$superAdminRole->display_name} (所有权限)");
        $this->command->info("  - 管理员: {$adminRole->display_name} (" . $adminPermissions->count() . " 个权限)");
        $this->command->info("  - 操作员: {$operatorRole->display_name} (" . $operatorPermissions->count() . " 个权限)");
    }
}
