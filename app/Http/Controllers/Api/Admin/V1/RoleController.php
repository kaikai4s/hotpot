<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin\V1;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class RoleController extends Controller
{
    /**
     * 获取所有角色列表
     */
    public function index(): JsonResponse
    {
        $roles = Role::with('permissions')->orderBy('sort_order')->get();

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'roles' => $roles,
            ],
        ]);
    }

    /**
     * 创建角色
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:50|unique:roles,name',
            'display_name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'permission_ids' => 'nullable|array',
            'permission_ids.*' => 'integer|exists:permissions,id',
        ]);

        $role = Role::create([
            'name' => $request->name,
            'display_name' => $request->display_name,
            'description' => $request->description,
            'is_system' => false,
        ]);

        if ($request->has('permission_ids')) {
            $role->permissions()->sync($request->permission_ids);
        }

        $role->load('permissions');

        return response()->json([
            'code' => 201,
            'message' => '角色创建成功',
            'data' => [
                'role' => $role,
            ],
        ], 201);
    }

    /**
     * 更新角色
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $role = Role::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|string|max:50|unique:roles,name,' . $id,
            'display_name' => 'sometimes|string|max:100',
            'description' => 'nullable|string',
            'permission_ids' => 'nullable|array',
            'permission_ids.*' => 'integer|exists:permissions,id',
        ]);

        // 系统角色：只允许修改权限，不允许修改基本信息（name, display_name, description）
        if ($role->is_system) {
            // 如果尝试修改基本信息，则拒绝
            if ($request->has('name') || $request->has('display_name') || $request->has('description')) {
                throw ValidationException::withMessages([
                    'role' => ['系统角色的名称、标识和描述不能修改，只能修改权限'],
                ])->status(403);
            }

            // 只允许修改权限
            if ($request->has('permission_ids')) {
                $role->permissions()->sync($request->permission_ids);
            }
        } else {
            // 非系统角色：可以修改所有信息
            $role->update($request->only([
                'name',
                'display_name',
                'description',
            ]));

            if ($request->has('permission_ids')) {
                $role->permissions()->sync($request->permission_ids);
            }
        }

        $role->load('permissions');

        return response()->json([
            'code' => 200,
            'message' => '角色更新成功',
            'data' => [
                'role' => $role,
            ],
        ]);
    }

    /**
     * 删除角色
     */
    public function destroy(int $id): JsonResponse
    {
        $role = Role::findOrFail($id);

        if ($role->is_system) {
            throw ValidationException::withMessages([
                'role' => ['系统角色不能删除'],
            ])->status(403);
        }

        // 检查是否有管理员使用此角色
        if ($role->admins()->exists()) {
            throw ValidationException::withMessages([
                'role' => ['该角色正在被使用，无法删除'],
            ])->status(409);
        }

        $role->delete();

        return response()->json([
            'code' => 200,
            'message' => '角色删除成功',
        ]);
    }
}
