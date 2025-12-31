<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin\V1;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AdminController extends Controller
{
    /**
     * 获取管理员列表
     */
    public function index(Request $request): JsonResponse
    {
        $query = Admin::with('roles');

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('username', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->has('role')) {
            $role = $request->input('role');
            $query->where('role', $role);
        }

        $admins = $query->orderBy('created_at', 'desc')->paginate($request->input('page_size', 10));

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'admins' => $admins->items(),
                'pagination' => [
                    'total' => $admins->total(),
                    'current_page' => $admins->currentPage(),
                    'last_page' => $admins->lastPage(),
                    'per_page' => $admins->perPage(),
                ],
            ],
        ]);
    }

    /**
     * 获取管理员详情
     */
    public function show(int $id): JsonResponse
    {
        $admin = Admin::with('roles')->findOrFail($id);

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'admin' => $admin,
            ],
        ]);
    }

    /**
     * 创建管理员
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'username' => 'required|string|max:64|unique:admins,username',
            'email' => 'nullable|email|max:255|unique:admins,email',
            'password' => 'required|string|min:6',
            'name' => 'required|string|max:64',
            'role' => 'required|string|in:super_admin,admin,operator',
            'role_ids' => 'nullable|array',
            'role_ids.*' => 'exists:roles,id',
            'is_active' => 'sometimes|boolean',
        ]);

        $admin = Admin::create([
            'username' => $request->input('username'),
            'email' => $request->input('email'),
            'password' => Hash::make($request->input('password')),
            'name' => $request->input('name'),
            'role' => $request->input('role'),
            'is_active' => $request->input('is_active', true),
        ]);

        // 分配角色
        if ($request->has('role_ids')) {
            $admin->roles()->sync($request->input('role_ids'));
        } else {
            // 如果没有指定角色，根据role字段分配默认角色
            $role = \App\Models\Role::where('name', $request->input('role'))->first();
            if ($role) {
                $admin->roles()->attach($role->id);
            }
        }

        $admin->load('roles');

        return response()->json([
            'code' => 200,
            'message' => '管理员创建成功',
            'data' => [
                'admin' => $admin,
            ],
        ], 201);
    }

    /**
     * 更新管理员信息
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $admin = Admin::findOrFail($id);

        $request->validate([
            'username' => 'sometimes|string|max:64|unique:admins,username,' . $id,
            'email' => 'nullable|email|max:255|unique:admins,email,' . $id,
            'password' => 'sometimes|string|min:6',
            'name' => 'sometimes|string|max:64',
            'role' => 'sometimes|string|in:super_admin,admin,operator',
            'role_ids' => 'nullable|array',
            'role_ids.*' => 'exists:roles,id',
            'is_active' => 'sometimes|boolean',
        ]);

        $updateData = $request->only(['username', 'email', 'name', 'role', 'is_active']);

        if ($request->has('password')) {
            $updateData['password'] = Hash::make($request->input('password'));
        }

        $admin->update($updateData);

        // 更新角色分配
        if ($request->has('role_ids')) {
            $admin->roles()->sync($request->input('role_ids'));
        } elseif ($request->has('role')) {
            // 如果只更新了role字段，同步更新角色关联
            $role = \App\Models\Role::where('name', $request->input('role'))->first();
            if ($role) {
                $admin->roles()->sync([$role->id]);
            }
        }

        $admin->load('roles');

        return response()->json([
            'code' => 200,
            'message' => '管理员信息更新成功',
            'data' => [
                'admin' => $admin,
            ],
        ]);
    }

    /**
     * 删除管理员
     */
    public function destroy(int $id): JsonResponse
    {
        $admin = Admin::findOrFail($id);

        // 防止删除自己
        if ($admin->id === auth('admin')->id()) {
            throw ValidationException::withMessages([
                'admin' => ['不能删除自己的账号'],
            ]);
        }

        // 删除角色关联
        $admin->roles()->detach();

        $admin->delete();

        return response()->json([
            'code' => 200,
            'message' => '管理员删除成功',
        ]);
    }
}

