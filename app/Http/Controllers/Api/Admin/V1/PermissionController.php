<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin\V1;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\Http\JsonResponse;

class PermissionController extends Controller
{
    /**
     * 获取所有权限列表
     */
    public function index(): JsonResponse
    {
        $permissions = Permission::orderBy('group')->orderBy('sort_order')->get();

        // 按分组组织权限
        $groupedPermissions = $permissions->groupBy('group')->map(function ($group) {
            return $group->map(function ($permission) {
                return [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'display_name' => $permission->display_name,
                    'description' => $permission->description,
                    'sort_order' => $permission->sort_order,
                ];
            })->values();
        });

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'permissions' => $permissions,
                'grouped_permissions' => $groupedPermissions,
            ],
        ]);
    }
}
