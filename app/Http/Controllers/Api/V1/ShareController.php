<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\ShareService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ShareController extends Controller
{
    public function __construct(
        private ShareService $shareService
    ) {
    }

    /**
     * 记录分享
     */
    public function record(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'code' => 401,
                'message' => '未登录',
            ], 401);
        }

        $request->validate([
            'share_type' => 'required|string|in:review,order,achievement,task',
            'share_content_id' => 'required|integer|min:1',
            'share_platform' => 'sometimes|string|in:wechat,moments',
        ]);

        try {
            $shareType = $request->input('share_type');
            $shareContentId = $request->input('share_content_id');
            $sharePlatform = $request->input('share_platform', 'moments');

            // 验证分享内容是否存在
            $this->validateShareContent($shareType, $shareContentId, $user);

            $userShare = $this->shareService->recordShare(
                $user,
                $shareType,
                $shareContentId,
                $sharePlatform
            );

            return response()->json([
                'code' => 200,
                'message' => '分享记录成功',
                'data' => [
                    'id' => $userShare->id,
                    'share_type' => $userShare->share_type,
                    'share_content_id' => $userShare->share_content_id,
                    'share_platform' => $userShare->share_platform,
                    'reward_points' => $userShare->reward_points,
                    'reward_issued' => $userShare->reward_issued,
                    'can_get_reward' => $userShare->reward_points > 0 && !$userShare->reward_issued,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 400,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * 获取分享统计
     */
    public function stats(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'code' => 401,
                'message' => '未登录',
            ], 401);
        }

        $shareType = $request->input('type');
        $stats = $this->shareService->getShareStats($user, $shareType);

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => $stats,
        ]);
    }

    /**
     * 获取分享列表
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'code' => 401,
                'message' => '未登录',
            ], 401);
        }

        $shareType = $request->input('type');
        $limit = (int) $request->input('limit', 20);

        $shares = $this->shareService->getUserShares($user, $shareType, $limit);

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'shares' => $shares,
            ],
        ]);
    }

    /**
     * 验证分享内容是否存在
     */
    private function validateShareContent(string $shareType, int $shareContentId, $user): void
    {
        switch ($shareType) {
            case 'review':
                $exists = \App\Models\Review::where('id', $shareContentId)
                    ->where('user_id', $user->id)
                    ->exists();
                if (!$exists) {
                    throw new \Exception('评价不存在或不属于您');
                }
                break;

            case 'order':
                $exists = \App\Models\Order::where('id', $shareContentId)
                    ->where('user_id', $user->id)
                    ->exists();
                if (!$exists) {
                    throw new \Exception('订单不存在或不属于您');
                }
                break;

            case 'achievement':
                $exists = \App\Models\UserAchievement::where('id', $shareContentId)
                    ->where('user_id', $user->id)
                    ->exists();
                if (!$exists) {
                    throw new \Exception('成就不存在或不属于您');
                }
                break;

            case 'task':
                $exists = \App\Models\UserTask::where('id', $shareContentId)
                    ->where('user_id', $user->id)
                    ->exists();
                if (!$exists) {
                    throw new \Exception('任务不存在或不属于您');
                }
                break;

            default:
                throw new \Exception('无效的分享类型');
        }
    }
}

