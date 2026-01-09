<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Services;

use App\Models\BirthdayDessertVoucher;
use App\Models\BirthdayPrivilegeLog;
use App\Models\Coupon;
use App\Models\User;
use App\Models\UserBirthday;
use App\Models\UserCoupon;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BirthdayPrivilegeService
{
    /**
     * 各会员等级对应的生日优惠券面额
     */
    private const COUPON_AMOUNTS = [
        'bronze' => 20,
        'silver' => 30,
        'gold' => 50,
        'platinum' => 100,
    ];

    /**
     * 设置用户生日
     */
    public function setBirthday(User $user, Carbon $birthday): UserBirthday
    {
        $currentYear = now()->year;

        return DB::transaction(function () use ($user, $birthday, $currentYear) {
            $userBirthday = UserBirthday::where('user_id', $user->id)->first();

            if ($userBirthday) {
                // 检查是否可以修改
                if (!$this->canModifyBirthday($user)) {
                    throw new \Exception('每年只能修改一次生日', 409);
                }

                $userBirthday->update([
                    'birthday' => $birthday->format('Y-m-d'),
                    'last_modified_at' => now(),
                    'last_modified_year' => $currentYear,
                ]);
            } else {
                $userBirthday = UserBirthday::create([
                    'user_id' => $user->id,
                    'birthday' => $birthday->format('Y-m-d'),
                    'last_modified_at' => now(),
                    'last_modified_year' => $currentYear,
                ]);
            }

            return $userBirthday;
        });
    }

    /**
     * 检查用户是否可以修改生日（每年限改一次）
     */
    public function canModifyBirthday(User $user): bool
    {
        $userBirthday = UserBirthday::where('user_id', $user->id)->first();

        if (!$userBirthday) {
            return true;
        }

        $currentYear = now()->year;
        return $userBirthday->last_modified_year !== $currentYear;
    }

    /**
     * 获取用户生日信息
     */
    public function getBirthdayInfo(User $user): ?UserBirthday
    {
        return UserBirthday::where('user_id', $user->id)->first();
    }

    /**
     * 判断指定日期是否为用户生日
     */
    public function isBirthday(User $user, ?Carbon $date = null): bool
    {
        $userBirthday = $this->getBirthdayInfo($user);
        if (!$userBirthday) {
            return false;
        }

        $checkDate = $date ?? Carbon::today();
        $birthday = $userBirthday->birthday;

        return $checkDate->month === $birthday->month && $checkDate->day === $birthday->day;
    }

    /**
     * 判断指定日期是否在用户生日期间（生日当天或生日后7天内）
     */
    public function isInBirthdayPeriod(User $user, ?Carbon $date = null): bool
    {
        $userBirthday = $this->getBirthdayInfo($user);
        if (!$userBirthday) {
            return false;
        }

        $checkDate = $date ?? Carbon::today();
        $birthday = $userBirthday->birthday;

        // 获取今年的生日日期
        $thisYearBirthday = Carbon::create($checkDate->year, $birthday->month, $birthday->day);
        
        // 如果今年的生日已过，检查是否在生日后7天内
        $periodEnd = $thisYearBirthday->copy()->addDays(7);

        return $checkDate->between($thisYearBirthday, $periodEnd);
    }

    /**
     * 发放生日优惠券
     */
    public function issueBirthdayCoupon(User $user): ?UserCoupon
    {
        // 检查是否已领取过今年的生日优惠券
        if ($this->hasBirthdayCouponThisYear($user)) {
            return null;
        }

        // 获取用户会员等级
        $memberPoint = $user->memberPoints;
        $level = $memberPoint?->level ?? 'bronze';

        // 获取对应面额
        $amount = $this->getBirthdayCouponAmount($level);

        return DB::transaction(function () use ($user, $amount, $level) {
            // 创建或获取生日优惠券模板
            $coupon = Coupon::firstOrCreate(
                ['name' => '生日专属优惠券-' . $level],
                [
                    'type' => 'fixed_amount',
                    'value' => $amount,
                    'min_amount' => 0,
                    'points_required' => 0,
                    'stock' => 999999,
                    'valid_from' => now()->startOfYear(),
                    'valid_to' => now()->endOfYear(),
                    'is_active' => true,
                    'is_new_user_only' => false,
                    'description' => "生日专属{$amount}元优惠券",
                ]
            );

            // 计算有效期（生日当天起30天）
            $userBirthday = $this->getBirthdayInfo($user);
            $birthdayThisYear = Carbon::create(now()->year, $userBirthday->birthday->month, $userBirthday->birthday->day);
            $expiresAt = $birthdayThisYear->copy()->addDays(30);

            // 发放优惠券给用户
            $userCoupon = UserCoupon::create([
                'user_id' => $user->id,
                'coupon_id' => $coupon->id,
                'code' => 'BD' . strtoupper(Str::random(10)),
                'status' => 'unused',
                'expires_at' => $expiresAt,
                'obtained_from' => 'birthday',
                'obtained_at' => now(),
            ]);

            // 记录发放日志
            BirthdayPrivilegeLog::create([
                'user_id' => $user->id,
                'year' => now()->year,
                'privilege_type' => BirthdayPrivilegeLog::TYPE_COUPON,
                'reference_id' => $userCoupon->id,
                'issued_at' => now(),
            ]);

            return $userCoupon;
        });
    }

    /**
     * 检查用户今年是否已领取生日优惠券
     */
    public function hasBirthdayCouponThisYear(User $user): bool
    {
        return BirthdayPrivilegeLog::where('user_id', $user->id)
            ->where('year', now()->year)
            ->where('privilege_type', BirthdayPrivilegeLog::TYPE_COUPON)
            ->exists();
    }

    /**
     * 根据会员等级获取生日优惠券面额
     */
    public function getBirthdayCouponAmount(string $level): int
    {
        return self::COUPON_AMOUNTS[$level] ?? self::COUPON_AMOUNTS['bronze'];
    }

    /**
     * 发放生日甜品券
     */
    public function issueBirthdayDessertVoucher(User $user): ?BirthdayDessertVoucher
    {
        // 检查是否已领取过今年的生日甜品券
        if ($this->hasBirthdayDessertThisYear($user)) {
            return null;
        }

        $userBirthday = $this->getBirthdayInfo($user);
        if (!$userBirthday) {
            return null;
        }

        return DB::transaction(function () use ($user, $userBirthday) {
            // 计算有效期（生日后7天）
            $birthdayThisYear = Carbon::create(
                now()->year,
                $userBirthday->birthday->month,
                $userBirthday->birthday->day
            );
            $expiresAt = $birthdayThisYear->copy()->addDays(7)->endOfDay();

            // 创建甜品券
            $voucher = BirthdayDessertVoucher::create([
                'user_id' => $user->id,
                'year' => now()->year,
                'code' => 'DS' . strtoupper(Str::random(10)),
                'status' => BirthdayDessertVoucher::STATUS_UNUSED,
                'expires_at' => $expiresAt,
                'created_at' => now(),
            ]);

            // 记录发放日志
            BirthdayPrivilegeLog::create([
                'user_id' => $user->id,
                'year' => now()->year,
                'privilege_type' => BirthdayPrivilegeLog::TYPE_DESSERT,
                'reference_id' => $voucher->id,
                'issued_at' => now(),
            ]);

            return $voucher;
        });
    }

    /**
     * 检查用户今年是否已领取生日甜品券
     */
    public function hasBirthdayDessertThisYear(User $user): bool
    {
        return BirthdayDessertVoucher::where('user_id', $user->id)
            ->where('year', now()->year)
            ->exists();
    }

    /**
     * 使用生日甜品券
     */
    public function useDessertVoucher(BirthdayDessertVoucher $voucher, int $orderId): bool
    {
        if ($voucher->status !== BirthdayDessertVoucher::STATUS_UNUSED) {
            throw new \Exception('甜品券已使用或已过期', 409);
        }

        if ($voucher->expires_at->isPast()) {
            $voucher->update(['status' => BirthdayDessertVoucher::STATUS_EXPIRED]);
            throw new \Exception('甜品券已过期', 409);
        }

        $voucher->update([
            'status' => BirthdayDessertVoucher::STATUS_USED,
            'used_at' => now(),
            'order_id' => $orderId,
        ]);

        return true;
    }

    /**
     * 获取用户可用的生日甜品券
     */
    public function getAvailableDessertVoucher(User $user): ?BirthdayDessertVoucher
    {
        return BirthdayDessertVoucher::where('user_id', $user->id)
            ->where('year', now()->year)
            ->where('status', BirthdayDessertVoucher::STATUS_UNUSED)
            ->where('expires_at', '>', now())
            ->first();
    }

    /**
     * 计算生日积分倍数
     * 生日当天返回2.0（双倍积分）
     */
    public function calculateBirthdayPointsMultiplier(User $user, ?Carbon $date = null): float
    {
        if ($this->isBirthday($user, $date)) {
            return 2.0;
        }
        return 1.0;
    }

    /**
     * 发送生日提醒通知（生日前7天）
     */
    public function sendBirthdayReminder(User $user): void
    {
        // TODO: 实现通知发送逻辑
        // 可以通过事件或队列发送微信模板消息
    }

    /**
     * 获取即将过生日的用户（未来N天内）
     */
    public function getUsersWithUpcomingBirthday(int $daysAhead = 7): \Illuminate\Database\Eloquent\Collection
    {
        $targetDate = Carbon::today()->addDays($daysAhead);
        
        return UserBirthday::whereRaw('MONTH(birthday) = ? AND DAY(birthday) = ?', [
            $targetDate->month,
            $targetDate->day,
        ])->with('user')->get();
    }
}
