<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Helpers\LoggerHelper;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\PhoneVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    /**
     * 更新用户资料
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'code' => 401,
                'message' => '未登录',
            ], 401);
        }

        // 【修复】先处理avatar_url，将空字符串转换为null，然后再验证
        // 这样可以避免空字符串触发url验证规则失败
        $avatarUrl = $request->input('avatar_url');
        if ($avatarUrl === '') {
            $request->merge(['avatar_url' => null]);
        }

        $request->validate([
            'nickname' => 'nullable|string|max:64',
            // 【修复】允许相对路径（以/开头）或完整URL（http://或https://开头）
            // 这样可以支持前端使用相对路径（/storage/...）或完整URL
            'avatar_url' => [
                'nullable',
                'string',
                'max:255',
                function ($attribute, $value, $fail) {
                    if ($value === null || $value === '') {
                        return; // null或空字符串允许
                    }
                    // 允许相对路径（以/开头）
                    if (str_starts_with($value, '/')) {
                        return;
                    }
                    // 允许完整URL（http://或https://开头）
                    if (filter_var($value, FILTER_VALIDATE_URL) !== false) {
                        return;
                    }
                    // 其他情况都不允许
                    $fail('头像URL必须是有效的网址格式（如：https://example.com/image.jpg）或相对路径（如：/storage/uploads/images/xxx.png）');
                },
            ],
            'phone' => 'nullable|string|regex:/^1[3-9]\d{9}$/|unique:users,phone,' . $user->id,
            'gender' => 'nullable|integer|in:0,1,2',
            'password' => 'nullable|string|min:6|confirmed',
            'current_password' => 'nullable|string', // 如果用户没有密码，则不需要当前密码
        ]);

        $updateData = [];

        // 更新昵称
        if ($request->has('nickname')) {
            $updateData['nickname'] = $request->input('nickname');
        }

        // 更新头像
        if ($request->has('avatar_url')) {
            $avatarUrl = $request->input('avatar_url');
            // 【修复】如果头像URL为空字符串，转换为null，允许清空头像
            // 这样既符合nullable验证规则，又允许用户清空头像
            $updateData['avatar_url'] = $avatarUrl === '' ? null : $avatarUrl;
        }

        // 更新性别
        if ($request->has('gender')) {
            $updateData['gender'] = $request->input('gender');
        }

        // 更新手机号（需要验证码）
        // 包括首次绑定和修改手机号两种情况
        if ($request->has('phone')) {
            $phone = $request->input('phone');
            $currentPhone = $user->phone ?? '';
            
            // 如果手机号有变化（包括从空到有值的情况）
            if ($phone !== $currentPhone) {
                $code = $request->input('phone_verification_code');

                if (!$code) {
                    return response()->json([
                        'code' => 400,
                        'message' => $currentPhone ? '修改手机号需要验证码' : '绑定手机号需要验证码',
                    ], 400);
                }

                // 验证验证码
                $phoneVerificationService = app(PhoneVerificationService::class);
                $verifyResult = $phoneVerificationService->verifyCode($phone, $code, 'register');

                if (!$verifyResult['success']) {
                    return response()->json([
                        'code' => 400,
                        'message' => $verifyResult['message'],
                    ], 400);
                }

                $updateData['phone'] = $phone;

                LoggerHelper::userInfo('用户修改手机号', [
                    'user_id' => $user->id,
                    'old_phone' => $user->phone ? substr($user->phone, 0, 3) . '****' . substr($user->phone, -4) : null,
                    'new_phone' => substr($phone, 0, 3) . '****' . substr($phone, -4),
                ]);
            }
        }

        // 更新密码
        if ($request->has('password')) {
            $currentPassword = $request->input('current_password');
            $newPassword = $request->input('password');

            // 如果用户已经设置过密码，需要验证当前密码
            if ($user->password) {
                if (!$currentPassword) {
                    return response()->json([
                        'code' => 400,
                        'message' => '请输入当前密码',
                    ], 400);
                }
                
                if (!Hash::check($currentPassword, $user->password)) {
                    return response()->json([
                        'code' => 400,
                        'message' => '当前密码错误',
                    ], 400);
                }
            }
            // 如果用户没有设置过密码，可以直接设置，不需要当前密码

            $updateData['password'] = Hash::make($newPassword);

            LoggerHelper::userInfo($user->password ? '用户修改密码' : '用户首次设置密码', [
                'user_id' => $user->id,
            ]);
        }

        // 更新用户信息
        if (!empty($updateData)) {
            $user->update($updateData);

            LoggerHelper::userInfo('用户更新资料', [
                'user_id' => $user->id,
                'updated_fields' => array_keys($updateData),
            ]);
        }

        // 刷新用户数据
        $user->refresh();
        
        // 获取更新后的用户信息
        $memberPoint = $user->memberPoints;
        $level = null;
        if ($memberPoint) {
            $level = \App\Models\PointLevel::where('code', $memberPoint->level)->first();
        }

        return response()->json([
            'code' => 200,
            'message' => '更新成功',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'nickname' => $user->nickname,
                    'avatar_url' => $user->avatar_url,
                    'phone' => $user->phone,
                    'gender' => $user->gender,
                    'equipped_title' => $user->equipped_title,
                    'has_password' => !empty($user->password), // 返回是否已设置密码
                    'level' => $level ? [
                        'code' => $level->code,
                        'name' => $level->name,
                        'icon' => $level->icon,
                        'color' => $level->color,
                    ] : null,
                ],
            ],
        ]);
    }

    /**
     * 设置密码（首次设置，不需要当前密码）
     */
    public function setPassword(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'code' => 401,
                'message' => '未登录',
            ], 401);
        }

        $request->validate([
            'password' => 'required|string|min:6|confirmed',
        ]);

        // 【修复】记录是否为首次设置密码（在设置之前判断）
        $isFirstTime = empty($user->password);

        // 如果已有密码，需要当前密码验证
        if ($user->password) {
            $request->validate([
                'current_password' => 'required|string',
            ]);

            if (!Hash::check($request->input('current_password'), $user->password)) {
                return response()->json([
                    'code' => 400,
                    'message' => '当前密码错误',
                ], 400);
            }
        }

        $user->password = Hash::make($request->input('password'));
        $user->save();

        LoggerHelper::userInfo($isFirstTime ? '用户首次设置密码' : '用户修改密码', [
            'user_id' => $user->id,
            'is_first_time' => $isFirstTime,
        ]);

        return response()->json([
            'code' => 200,
            'message' => '密码设置成功',
        ]);
    }
}
