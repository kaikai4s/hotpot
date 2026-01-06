<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Models\UserInvitation;
use App\Models\Coupon;
use App\Models\UserCoupon;
use App\Services\PointService;
use App\Services\AchievementService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class InvitationService
{
    public function __construct(
        private PointService $pointService,
        private AchievementService $achievementService
    ) {
    }

    /**
     * 生成邀请码
     * 格式：INV{user_id}{6位随机字符}
     */
    public function generateInviteCode(User $user): string
    {
        if ($user->invite_code) {
            return $user->invite_code;
        }

        // 确保邀请码唯一
        do {
            $random = Str::upper(Str::random(6));
            $inviteCode = 'INV' . str_pad((string)$user->id, 6, '0', STR_PAD_LEFT) . $random;
        } while (User::where('invite_code', $inviteCode)->exists());

        $user->update(['invite_code' => $inviteCode]);

        return $inviteCode;
    }

    /**
     * 使用邀请码注册
     */
    public function registerWithInviteCode(User $user, string $inviteCode): ?UserInvitation
    {
        // 查找邀请人
        $inviter = User::where('invite_code', $inviteCode)->first();
        if (!$inviter) {
            return null;
        }

        // 不能邀请自己
        if ($inviter->id === $user->id) {
            return null;
        }

        // 检查是否已经被邀请过
        $existingInvitation = UserInvitation::where('invitee_id', $user->id)->first();
        if ($existingInvitation) {
            return $existingInvitation;
        }

        // 创建邀请记录
        $invitation = UserInvitation::create([
            'inviter_id' => $inviter->id,
            'invitee_id' => $user->id,
            'invite_code' => $inviteCode,
            'status' => 'registered',
            'registered_at' => now(),
        ]);

        // 更新用户的invited_by字段
        $user->update(['invited_by' => $inviter->id]);

        // 发放被邀请人新人礼包
        $this->issueNewUserReward($user);

        Log::info('用户使用邀请码注册', [
            'inviter_id' => $inviter->id,
            'invitee_id' => $user->id,
            'invite_code' => $inviteCode,
        ]);

        return $invitation;
    }

    /**
     * 发放被邀请人新人礼包
     */
    private function issueNewUserReward(User $user): void
    {
        DB::transaction(function () use ($user) {
            // 发放100积分
            $this->pointService->earnPoints(
                $user,
                100,
                'invite_new_user',
                null,
                '新人注册礼包'
            );

            // 发放新人优惠券（优先查找新人专享优惠券，否则查找10元现金券）
            $coupon = Coupon::where('is_active', true)
                ->where('stock', '>', 0)
                ->where(function ($query) {
                    $query->where('is_new_user_only', true)
                        ->orWhere(function ($q) {
                            $q->where('type', 'cash')
                                ->where('value', 10);
                        });
                })
                ->orderByDesc('is_new_user_only')
                ->first();

            if ($coupon) {
                UserCoupon::create([
                    'user_id' => $user->id,
                    'coupon_id' => $coupon->id,
                    'status' => 'unused',
                    'expires_at' => now()->addDays(30),
                ]);

                // 减少优惠券库存
                $coupon->decrement('stock');
            }
        });
    }

    /**
     * 发放邀请奖励（被邀请人首次消费后）
     */
    public function issueInvitationRewards(UserInvitation $invitation): void
    {
        if ($invitation->reward_issued) {
            return;
        }

        DB::transaction(function () use ($invitation) {
            // 发放邀请人200积分奖励
            $this->pointService->earnPoints(
                $invitation->inviter,
                200,
                'invite_reward',
                $invitation->id,
                '邀请好友首次消费奖励'
            );

            // 更新邀请记录
            $invitation->update([
                'status' => 'completed',
                'reward_issued' => true,
            ]);

            // 检测成就完成（邀请类成就）
            try {
                $this->achievementService->checkAchievementCompletion($invitation->inviter, 'invite', 1);
            } catch (\Exception $e) {
                Log::warning('邀请成就完成检测失败', [
                    'invitation_id' => $invitation->id,
                    'error' => $e->getMessage(),
                ]);
            }

            Log::info('邀请奖励已发放', [
                'invitation_id' => $invitation->id,
                'inviter_id' => $invitation->inviter_id,
                'invitee_id' => $invitation->invitee_id,
            ]);
        });
    }

    /**
     * 获取用户的邀请统计
     */
    public function getInvitationStats(User $user): array
    {
        // 确保用户有邀请码
        if (!$user->invite_code) {
            $this->generateInviteCode($user);
            $user->refresh();
        }

        $invitations = UserInvitation::where('inviter_id', $user->id)
            ->with(['invitee.memberPoints'])
            ->get();

        $totalInvites = $invitations->count();
        $successfulInvites = $invitations->where('status', 'completed')->count();
        
        // 统计邀请人获得的积分奖励总额
        $totalRewardsPoints = $user->pointTransactions()
            ->where('source_type', 'invite_reward')
            ->where('type', 'earn')
            ->sum('points');

        $friends = $invitations->map(function ($inv) {
            $invitee = $inv->invitee;
            $level = null;
            if ($invitee && $invitee->memberPoints) {
                $levelModel = \App\Models\PointLevel::where('code', $invitee->memberPoints->level)->first();
                if ($levelModel) {
                    $level = [
                        'code' => $levelModel->code,
                        'name' => $levelModel->name,
                        'icon' => $levelModel->icon,
                        'color' => $levelModel->color,
                    ];
                }
            }

            return [
                'id' => $inv->invitee_id ?? null,
                'nickname' => $invitee->nickname ?? '未知用户',
                'avatar_url' => $invitee->avatar_url ?? null,
                'equipped_title' => $invitee->equipped_title ?? null,
                'level' => $level,
                'status' => $inv->status,
                'registered_at' => $inv->registered_at?->format('Y-m-d H:i:s'),
                'first_order_at' => $inv->first_order_at?->format('Y-m-d H:i:s'),
                'reward_issued' => $inv->reward_issued,
            ];
        })->toArray();

        return [
            'invite_code' => $user->invite_code,
            'total_invites' => $totalInvites,
            'successful_invites' => $successfulInvites,
            'total_rewards_points' => (int) $totalRewardsPoints,
            'friends' => $friends,
        ];
    }

    /**
     * 获取邀请的好友列表
     */
    public function getInvitedFriends(User $user, int $perPage = 20): array
    {
        $invitations = UserInvitation::where('inviter_id', $user->id)
            ->with('invitee')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return [
            'friends' => $invitations->items(),
            'pagination' => [
                'current_page' => $invitations->currentPage(),
                'last_page' => $invitations->lastPage(),
                'per_page' => $invitations->perPage(),
                'total' => $invitations->total(),
            ],
        ];
    }
}

