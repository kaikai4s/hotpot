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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * 管理员登录
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $admin = Admin::where('username', $request->username)->first();

        if (!$admin || !Hash::check($request->password, $admin->password)) {
            throw ValidationException::withMessages([
                'username' => ['用户名或密码错误'],
            ]);
        }

        if (!$admin->is_active) {
            throw ValidationException::withMessages([
                'username' => ['该账号已被禁用'],
            ]);
        }

        // 更新最后登录时间
        $admin->last_login_at = now();
        $admin->save();

        // 生成Token
        $token = $admin->createToken('admin-token')->plainTextToken;

        // 获取角色和权限
        $roles = $admin->roles()->with('permissions')->get();
        $permissions = $roles->flatMap->permissions->unique('id')->values();

        // 超级管理员拥有所有权限
        if ($admin->role === 'super_admin') {
            $permissions = \App\Models\Permission::all();
        }

        return response()->json([
            'code' => 200,
            'message' => '登录成功',
            'data' => [
                'token' => $token,
                'admin' => [
                    'id' => $admin->id,
                    'username' => $admin->username,
                    'name' => $admin->name,
                    'email' => $admin->email,
                    'role' => $admin->role,
                    'roles' => $roles->map(function ($role) {
                        return [
                            'id' => $role->id,
                            'name' => $role->name,
                            'display_name' => $role->display_name,
                        ];
                    }),
                    'permissions' => $permissions->map(function ($permission) {
                        return [
                            'id' => $permission->id,
                            'name' => $permission->name,
                            'display_name' => $permission->display_name,
                            'group' => $permission->group,
                        ];
                    }),
                ],
            ],
        ]);
    }

    /**
     * 获取当前登录管理员信息
     */
    public function me(Request $request): JsonResponse
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json([
                'code' => 401,
                'message' => '未登录',
            ], 401);
        }

        $roles = $admin->roles()->with('permissions')->get();
        $permissions = $roles->flatMap->permissions->unique('id')->values();
        
        // 超级管理员拥有所有权限
        if ($admin->role === 'super_admin') {
            $permissions = \App\Models\Permission::all();
        }

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'admin' => [
                    'id' => $admin->id,
                    'username' => $admin->username,
                    'name' => $admin->name,
                    'email' => $admin->email,
                    'role' => $admin->role,
                    'is_active' => $admin->is_active,
                    'last_login_at' => $admin->last_login_at?->toIso8601String(),
                    'roles' => $roles->map(function ($role) {
                        return [
                            'id' => $role->id,
                            'name' => $role->name,
                            'display_name' => $role->display_name,
                        ];
                    }),
                    'permissions' => $permissions->map(function ($permission) {
                        return [
                            'id' => $permission->id,
                            'name' => $permission->name,
                            'display_name' => $permission->display_name,
                            'group' => $permission->group,
                        ];
                    }),
                ],
            ],
        ]);
    }

    /**
     * 退出登录
     */
    public function logout(Request $request): JsonResponse
    {
        $admin = Auth::guard('admin')->user();
        
        if ($admin) {
            // 删除当前使用的token
            $admin->currentAccessToken()?->delete();
        }

        return response()->json([
            'code' => 200,
            'message' => '退出成功',
        ]);
    }
}
