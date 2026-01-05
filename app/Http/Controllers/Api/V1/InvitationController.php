<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\InvitationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class InvitationController extends Controller
{
    public function __construct(
        private InvitationService $invitationService
    ) {
    }

    /**
     * 获取我的邀请信息
     */
    public function getMyInvitation(): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'code' => 401,
                'message' => '未登录',
            ], 401);
        }

        $stats = $this->invitationService->getInvitationStats($user);

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => $stats,
        ]);
    }

    /**
     * 获取邀请的好友列表
     */
    public function getFriends(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'code' => 401,
                'message' => '未登录',
            ], 401);
        }

        $perPage = (int) $request->input('per_page', 20);
        $result = $this->invitationService->getInvitedFriends($user, $perPage);

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => $result,
        ]);
    }

    /**
     * 使用邀请码注册
     */
    public function registerWithInviteCode(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'code' => 401,
                'message' => '未登录',
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'invite_code' => 'required|string|max:32',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'code' => 400,
                'message' => '参数错误',
                'errors' => $validator->errors(),
            ], 400);
        }

        // 检查用户是否已经被邀请过
        if ($user->invited_by) {
            return response()->json([
                'code' => 400,
                'message' => '您已经使用过邀请码了',
            ], 400);
        }

        $inviteCode = $request->input('invite_code');
        $invitation = $this->invitationService->registerWithInviteCode($user, $inviteCode);

        if (!$invitation) {
            return response()->json([
                'code' => 400,
                'message' => '邀请码无效或已过期',
            ], 400);
        }

        return response()->json([
            'code' => 200,
            'message' => '注册成功，已获得新人礼包',
            'data' => [
                'invitation' => $invitation,
            ],
        ]);
    }
}

