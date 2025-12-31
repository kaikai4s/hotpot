<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // 桌位管理权限
            ['name' => 'tables.view', 'display_name' => '查看桌位', 'group' => 'tables', 'description' => '查看桌位列表和详情', 'sort_order' => 1],
            ['name' => 'tables.create', 'display_name' => '创建桌位', 'group' => 'tables', 'description' => '创建新桌位', 'sort_order' => 2],
            ['name' => 'tables.update', 'display_name' => '更新桌位', 'group' => 'tables', 'description' => '更新桌位信息', 'sort_order' => 3],
            ['name' => 'tables.delete', 'display_name' => '删除桌位', 'group' => 'tables', 'description' => '删除桌位', 'sort_order' => 4],
            ['name' => 'tables.position', 'display_name' => '管理桌位位置', 'group' => 'tables', 'description' => '编辑桌位位置和默认位置', 'sort_order' => 5],

            // 预约管理权限
            ['name' => 'reservations.view', 'display_name' => '查看预约', 'group' => 'reservations', 'description' => '查看预约列表和详情', 'sort_order' => 1],
            ['name' => 'reservations.update', 'display_name' => '更新预约', 'group' => 'reservations', 'description' => '更新预约状态和信息', 'sort_order' => 2],
            ['name' => 'reservations.delete', 'display_name' => '删除预约', 'group' => 'reservations', 'description' => '删除预约记录', 'sort_order' => 3],

            // 评价管理权限
            ['name' => 'reviews.view', 'display_name' => '查看评价', 'group' => 'reviews', 'description' => '查看评价列表和详情', 'sort_order' => 1],
            ['name' => 'reviews.approve', 'display_name' => '审核评价', 'group' => 'reviews', 'description' => '审核通过或拒绝评价', 'sort_order' => 2],
            ['name' => 'reviews.reply', 'display_name' => '回复评价', 'group' => 'reviews', 'description' => '回复用户评价', 'sort_order' => 3],
            ['name' => 'reviews.adopt', 'display_name' => '采纳评价', 'group' => 'reviews', 'description' => '采纳评价建议', 'sort_order' => 4],
            ['name' => 'reviews.track', 'display_name' => '追踪优化', 'group' => 'reviews', 'description' => '追踪优化已采纳的评价', 'sort_order' => 5],
            ['name' => 'reviews.delete', 'display_name' => '删除评价', 'group' => 'reviews', 'description' => '删除评价', 'sort_order' => 6],

            // 区域管理权限
            ['name' => 'areas.view', 'display_name' => '查看区域', 'group' => 'areas', 'description' => '查看区域列表和详情', 'sort_order' => 1],
            ['name' => 'areas.create', 'display_name' => '创建区域', 'group' => 'areas', 'description' => '创建新区域', 'sort_order' => 2],
            ['name' => 'areas.update', 'display_name' => '更新区域', 'group' => 'areas', 'description' => '更新区域信息', 'sort_order' => 3],
            ['name' => 'areas.delete', 'display_name' => '删除区域', 'group' => 'areas', 'description' => '删除区域', 'sort_order' => 4],

            // 角色管理权限
            ['name' => 'roles.view', 'display_name' => '查看角色', 'group' => 'roles', 'description' => '查看角色列表和详情', 'sort_order' => 1],
            ['name' => 'roles.create', 'display_name' => '创建角色', 'group' => 'roles', 'description' => '创建新角色', 'sort_order' => 2],
            ['name' => 'roles.update', 'display_name' => '更新角色', 'group' => 'roles', 'description' => '更新角色信息和权限', 'sort_order' => 3],
            ['name' => 'roles.delete', 'display_name' => '删除角色', 'group' => 'roles', 'description' => '删除角色', 'sort_order' => 4],

            // 管理员管理权限
            ['name' => 'admins.view', 'display_name' => '查看管理员', 'group' => 'admins', 'description' => '查看管理员列表和详情', 'sort_order' => 1],
            ['name' => 'admins.create', 'display_name' => '创建管理员', 'group' => 'admins', 'description' => '创建新管理员账号', 'sort_order' => 2],
            ['name' => 'admins.update', 'display_name' => '更新管理员', 'group' => 'admins', 'description' => '更新管理员信息和角色', 'sort_order' => 3],
            ['name' => 'admins.delete', 'display_name' => '删除管理员', 'group' => 'admins', 'description' => '删除管理员账号', 'sort_order' => 4],

            // 用户管理权限
            ['name' => 'users.view', 'display_name' => '查看用户', 'group' => 'users', 'description' => '查看用户列表和详情', 'sort_order' => 1],
            ['name' => 'users.update', 'display_name' => '更新用户', 'group' => 'users', 'description' => '更新用户信息', 'sort_order' => 2],
            ['name' => 'users.delete', 'display_name' => '删除用户', 'group' => 'users', 'description' => '删除用户账号', 'sort_order' => 3],

            // 配置管理权限
            ['name' => 'configs.view', 'display_name' => '查看配置', 'group' => 'configs', 'description' => '查看系统配置', 'sort_order' => 1],
            ['name' => 'configs.update', 'display_name' => '更新配置', 'group' => 'configs', 'description' => '更新系统配置', 'sort_order' => 2],

            // 积分系统管理权限
            ['name' => 'points.view', 'display_name' => '查看积分', 'group' => 'points', 'description' => '查看用户积分和交易记录', 'sort_order' => 1],
            ['name' => 'points.update', 'display_name' => '调整积分', 'group' => 'points', 'description' => '调整用户积分', 'sort_order' => 2],

            // 优惠活动管理权限
            ['name' => 'coupons.view', 'display_name' => '查看优惠券', 'group' => 'coupons', 'description' => '查看优惠券列表和详情', 'sort_order' => 1],
            ['name' => 'coupons.create', 'display_name' => '创建优惠券', 'group' => 'coupons', 'description' => '创建新优惠券', 'sort_order' => 2],
            ['name' => 'coupons.update', 'display_name' => '更新优惠券', 'group' => 'coupons', 'description' => '更新优惠券信息', 'sort_order' => 3],
            ['name' => 'coupons.delete', 'display_name' => '删除优惠券', 'group' => 'coupons', 'description' => '删除优惠券', 'sort_order' => 4],

            // 订单管理权限
            ['name' => 'orders.view', 'display_name' => '查看订单', 'group' => 'orders', 'description' => '查看订单列表和详情', 'sort_order' => 1],
            ['name' => 'orders.update', 'display_name' => '更新订单', 'group' => 'orders', 'description' => '更新订单状态和信息', 'sort_order' => 2],

            // 菜品管理权限
            ['name' => 'dishes.view', 'display_name' => '查看菜品', 'group' => 'dishes', 'description' => '查看菜品列表和详情', 'sort_order' => 1],
            ['name' => 'dishes.create', 'display_name' => '创建菜品', 'group' => 'dishes', 'description' => '创建新菜品', 'sort_order' => 2],
            ['name' => 'dishes.update', 'display_name' => '更新菜品', 'group' => 'dishes', 'description' => '更新菜品信息', 'sort_order' => 3],
            ['name' => 'dishes.delete', 'display_name' => '删除菜品', 'group' => 'dishes', 'description' => '删除菜品', 'sort_order' => 4],

            // 操作日志管理权限（仅超级管理员）
            ['name' => 'audit_logs.view', 'display_name' => '查看操作日志', 'group' => 'audit_logs', 'description' => '查看系统操作日志和统计信息', 'sort_order' => 1],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission['name']],
                $permission
            );
        }

        $this->command->info('权限数据已初始化完成！');
    }
}
