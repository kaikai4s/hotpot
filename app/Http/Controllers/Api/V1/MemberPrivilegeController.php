<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\BirthdayPrivilegeService;
use App\Services\MemberDayService;
use App\Services\MemberPrivilegeService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MemberPrivilegeController extends Controller
{
    public function __construct(
        private MemberPrivilegeService $privilegeService,
        private BirthdayPrivilegeService $birthdayService,
        private MemberDayService $memberDayService
    ) {
    }

    /**
     * 获取会员权益概览
     * GET /api/v1/member/privileges
     */
    public function index(): JsonResponse
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json([
                'code' => 401,
                'message' => '未登录',
            ], 401);
        }

        $overview = $this->privilegeService->getPrivilegeOverview($user);

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => $overview,
        ]);
    }

    /**
     * 获取权益统计
     * GET /api/v1/member/privileges/stats
     */
    public function stats(): JsonResponse
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json([
                'code' => 401,
                'message' => '未登录',
            ], 401);
        }

        $stats = $this->privilegeService->getPrivilegeStats($user);

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => $stats,
        ]);
    }

    /**
     * 获取生日信息
     * GET /api/v1/member/birthday
     */
    public function getBirthday(): JsonResponse
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json([
                'code' => 401,
                'message' => '未登录',
            ], 401);
        }

        $birthdayInfo = $this->birthdayService->getBirthdayInfo($user);
        $canModify = $this->birthdayService->canModifyBirthday($user);

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'birthday' => $birthdayInfo?->birthday?->format('Y-m-d'),
                'can_modify' => $canModify,
                'last_modified_year' => $birthdayInfo?->last_modified_year,
                'is_birthday_today' => $birthdayInfo ? $this->birthdayService->isBirthday($user) : false,
            ],
        ]);
    }

    /**
     * 设置/修改生日
     * POST /api/v1/member/birthday
     */
    public function setBirthday(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json([
                'code' => 401,
                'message' => '未登录',
            ], 401);
        }

        $request->validate([
            'birthday' => 'required|date|before:today',
        ]);

        try {
            $birthday = Carbon::parse($request->input('birthday'));
            $userBirthday = $this->birthdayService->setBirthday($user, $birthday);

            return response()->json([
                'code' => 200,
                'message' => '生日设置成功',
                'data' => [
                    'birthday' => $userBirthday->birthday->format('Y-m-d'),
                    'can_modify' => false,
                ],
            ]);
        } catch (\Exception $e) {
            $code = $e->getCode() ?: 400;
            return response()->json([
                'code' => $code,
                'message' => $e->getMessage(),
            ], $code >= 400 && $code < 600 ? $code : 400);
        }
    }

    /**
     * 获取生日特权状态
     * GET /api/v1/member/birthday/privileges
     */
    public function getBirthdayPrivileges(): JsonResponse
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json([
                'code' => 401,
                'message' => '未登录',
            ], 401);
        }

        $memberPoint = $user->memberPoints;
        $level = $memberPoint?->level ?? 'bronze';

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'is_birthday_today' => $this->birthdayService->isBirthday($user),
                'is_in_birthday_period' => $this->birthdayService->isInBirthdayPeriod($user),
                'has_coupon_this_year' => $this->birthdayService->hasBirthdayCouponThisYear($user),
                'has_dessert_this_year' => $this->birthdayService->hasBirthdayDessertThisYear($user),
                'coupon_amount' => $this->birthdayService->getBirthdayCouponAmount($level),
                'available_dessert_voucher' => $this->birthdayService->getAvailableDessertVoucher($user)?->toArray(),
                'points_multiplier' => $this->birthdayService->calculateBirthdayPointsMultiplier($user),
            ],
        ]);
    }

    /**
     * 获取会员日信息
     * GET /api/v1/member/member-day
     */
    public function getMemberDay(): JsonResponse
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json([
                'code' => 401,
                'message' => '未登录',
            ], 401);
        }

        $config = $this->memberDayService->getConfig();
        $memberPoint = $user->memberPoints;
        $level = $memberPoint?->level ?? 'bronze';

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'is_enabled' => $this->memberDayService->isEnabled(),
                'is_member_day_today' => $this->memberDayService->isMemberDay(),
                'day_of_month' => $config->day_of_month,
                'next_member_day' => $this->memberDayService->getNextMemberDay()->format('Y-m-d'),
                'days_until_member_day' => $this->memberDayService->getDaysUntilMemberDay(),
                'discount' => $this->memberDayService->getMemberDayDiscount($level),
                'points_bonus_rate' => $this->memberDayService->getMemberDayPointsBonus(),
            ],
        ]);
    }

    /**
     * 获取当前用户会员日折扣
     * GET /api/v1/member/member-day/discount
     */
    public function getMemberDayDiscount(): JsonResponse
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json([
                'code' => 401,
                'message' => '未登录',
            ], 401);
        }

        $memberPoint = $user->memberPoints;
        $level = $memberPoint?->level ?? 'bronze';
        $discount = $this->memberDayService->getMemberDayDiscount($level);
        $isMemberDay = $this->memberDayService->isMemberDay();

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'is_member_day' => $isMemberDay,
                'discount' => $discount,
                'discount_percent' => (1 - $discount) * 100,
                'applicable' => $isMemberDay && $this->memberDayService->isEnabled(),
            ],
        ]);
    }
}
