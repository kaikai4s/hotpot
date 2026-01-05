<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin\V1;

use App\Http\Controllers\Controller;
use App\Models\UserInvitation;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvitationStatisticsController extends Controller
{
    /**
     * 获取邀请统计数据
     */
    public function statistics(Request $request): JsonResponse
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $query = UserInvitation::query();

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('created_at', '<=', $endDate . ' 23:59:59');
        }

        // 总体统计
        $totalInvitations = (clone $query)->count();
        $registeredInvitations = (clone $query)->where('status', '!=', 'pending')->count();
        $completedInvitations = (clone $query)->where('status', 'completed')->count();
        $totalRewardsIssued = (clone $query)->where('reward_issued', true)->count();

        // 按状态统计
        $byStatus = (clone $query)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get()
            ->keyBy('status')
            ->map(fn($item) => $item->count)
            ->toArray();

        // 按日期统计
        $byDate = (clone $query)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn($item) => [
                'date' => $item->date,
                'count' => $item->count,
            ])
            ->toArray();

        // 邀请达人排行榜（邀请成功数最多的用户）
        $topInviters = UserInvitation::select('inviter_id', DB::raw('COUNT(*) as success_count'))
            ->where('status', 'completed')
            ->groupBy('inviter_id')
            ->orderByDesc('success_count')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                $inviter = User::find($item->inviter_id);
                return [
                    'user_id' => $item->inviter_id,
                    'nickname' => $inviter->nickname ?? '未知用户',
                    'avatar_url' => $inviter->avatar_url ?? null,
                    'success_count' => $item->success_count,
                ];
            })
            ->toArray();

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'total_invitations' => $totalInvitations,
                'registered_invitations' => $registeredInvitations,
                'completed_invitations' => $completedInvitations,
                'total_rewards_issued' => $totalRewardsIssued,
                'by_status' => $byStatus,
                'by_date' => $byDate,
                'top_inviters' => $topInviters,
            ],
        ]);
    }

    /**
     * 获取邀请列表
     */
    public function index(Request $request): JsonResponse
    {
        $query = UserInvitation::with(['inviter:id,nickname,avatar_url', 'invitee:id,nickname,avatar_url']);

        if ($request->has('inviter_id')) {
            $query->where('inviter_id', $request->input('inviter_id'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('start_date')) {
            $query->where('created_at', '>=', $request->input('start_date'));
        }

        if ($request->has('end_date')) {
            $query->where('created_at', '<=', $request->input('end_date') . ' 23:59:59');
        }

        $invitations = $query->orderBy('created_at', 'desc')->paginate($request->input('page_size', 20));

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'invitations' => $invitations->items(),
                'pagination' => [
                    'total' => $invitations->total(),
                    'current_page' => $invitations->currentPage(),
                    'last_page' => $invitations->lastPage(),
                    'per_page' => $invitations->perPage(),
                ],
            ],
        ]);
    }
}

