<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Services;

use App\Models\Review;
use App\Models\Order;
use App\Models\Dish;
use App\Models\User;
use App\Models\Admin;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class ReviewService
{
    public function __construct(
        private ProfanityFilterService $profanityFilter
    ) {
    }
    public function createReview(
        User $user,
        int $orderId,
        int $dishId,
        int $rating,
        ?string $content = null,
        ?array $images = null,
        ?array $tags = null
    ): Review {
        // 验证订单是否存在且处于待评价状态
        $order = Order::where('id', $orderId)
            ->where('user_id', $user->id)
            ->where('status', 'pending_review')
            ->firstOrFail();

        // 验证订单中是否包含该菜品
        $orderItem = $order->items()->where('dish_id', $dishId)->first();
        if (!$orderItem) {
            throw new \Exception('订单中不包含该菜品', 403);
        }

        // 检查是否已评价
        $existing = Review::where('user_id', $user->id)
            ->where('order_id', $orderId)
            ->where('dish_id', $dishId)
            ->first();

        if ($existing) {
            throw new \Exception('已评价过该菜品', 409);
        }

        // 频率限制检查
        $key = "review_rate_limit:{$user->id}";
        $count = Cache::get($key, 0);
        if ($count >= 5) {
            throw new \Exception('提交过于频繁，请稍后再试', 429);
        }

        return DB::transaction(function () use ($user, $orderId, $dishId, $rating, $content, $images, $tags, $key, $count) {
            // 检测不文明语言
            $profanityCheck = $this->profanityFilter->checkReview($content, $tags);
            
            // 如果包含不文明语言，抛出异常
            if ($profanityCheck['has_profanity']) {
                throw new \Exception('评价内容包含不文明语言，无法通过审核', 422);
            }

            // 创建评价，自动通过审核
            $review = Review::create([
                'user_id' => $user->id,
                'order_id' => $orderId,
                'dish_id' => $dishId,
                'rating' => $rating,
                'content' => $content,
                'images' => $images,
                'tags' => $tags,
                'status' => 'approved', // 自动通过
                'reviewed_at' => now(), // 设置审核时间
            ]);

            // 更新频率限制
            Cache::put($key, $count + 1, 3600); // 1小时

            // 更新菜品评分（评价已自动通过）
            $this->updateDishRating($dishId);

            // 检查订单是否所有菜品都已评价，如果是则自动完成订单
            $this->checkAndCompleteOrder($orderId, $user->id);

            return $review;
        });
    }

    public function updateDishRating(int $dishId): void
    {
        $dish = Dish::findOrFail($dishId);
        $approvedReviews = Review::where('dish_id', $dishId)
            ->where('status', 'approved')
            ->get();

        if ($approvedReviews->isEmpty()) {
            return;
        }

        $averageRating = $approvedReviews->avg('rating');
        $reviewCount = $approvedReviews->count();

        $dish->update([
            'average_rating' => round((float) $averageRating, 2),
            'review_count' => $reviewCount,
        ]);
    }

    public function approveReview(int $reviewId): Review
    {
        $review = Review::findOrFail($reviewId);
        $review->update([
            'status' => 'approved',
            'reviewed_at' => now(),
        ]);

        // 更新菜品评分
        $this->updateDishRating($review->dish_id);

        return $review;
    }

    public function rejectReview(int $reviewId, ?string $reason = null): Review
    {
        $review = Review::findOrFail($reviewId);
        $review->update([
            'status' => 'rejected',
            'reviewed_at' => now(),
        ]);

        return $review;
    }

    /**
     * 管理员回复评价
     */
    public function replyReview(int $reviewId, Admin $admin, string $reply): Review
    {
        $review = Review::findOrFail($reviewId);
        
        $review->update([
            'admin_reply' => $reply,
            'admin_replied_at' => now(),
            'admin_replied_by' => $admin->id,
        ]);

        return $review->fresh();
    }

    /**
     * 采纳评价建议
     */
    public function adoptReview(int $reviewId, Admin $admin): Review
    {
        $review = Review::findOrFail($reviewId);
        
        if ($review->is_adopted) {
            throw new \Exception('该评价已被采纳', 400);
        }

        $review->update([
            'is_adopted' => true,
            'adopted_at' => now(),
            'adopted_by' => $admin->id,
            'tracking_status' => 'in_progress', // 采纳后自动进入追踪状态
        ]);

        // 添加追踪更新记录
        $trackingUpdates = $review->tracking_updates ?? [];
        $trackingUpdates[] = [
            'action' => 'adopted',
            'admin_id' => $admin->id,
            'admin_name' => $admin->name ?? $admin->username,
            'message' => '评价建议已被采纳，开始追踪优化',
            'created_at' => now()->toDateTimeString(),
        ];
        $review->update(['tracking_updates' => $trackingUpdates]);

        return $review->fresh();
    }

    /**
     * 更新追踪状态
     */
    public function updateTrackingStatus(int $reviewId, Admin $admin, string $status, ?string $message = null): Review
    {
        $review = Review::findOrFail($reviewId);
        
        if (!in_array($status, ['pending', 'in_progress', 'completed', 'cancelled'])) {
            throw new \Exception('无效的追踪状态', 400);
        }

        $oldStatus = $review->tracking_status;
        $review->update([
            'tracking_status' => $status,
        ]);

        // 添加追踪更新记录
        $trackingUpdates = $review->tracking_updates ?? [];
        $trackingUpdates[] = [
            'action' => 'status_update',
            'old_status' => $oldStatus,
            'new_status' => $status,
            'admin_id' => $admin->id,
            'admin_name' => $admin->name ?? $admin->username,
            'message' => $message ?? "追踪状态已更新为：{$status}",
            'created_at' => now()->toDateTimeString(),
        ];
        $review->update(['tracking_updates' => $trackingUpdates]);

        return $review->fresh();
    }

    /**
     * 添加追踪更新记录
     */
    public function addTrackingUpdate(int $reviewId, Admin $admin, string $message): Review
    {
        $review = Review::findOrFail($reviewId);
        
        $trackingUpdates = $review->tracking_updates ?? [];
        $trackingUpdates[] = [
            'action' => 'update',
            'admin_id' => $admin->id,
            'admin_name' => $admin->name ?? $admin->username,
            'message' => $message,
            'created_at' => now()->toDateTimeString(),
        ];
        
        $review->update(['tracking_updates' => $trackingUpdates]);

        return $review->fresh();
    }

    /**
     * 检查订单是否所有菜品都已评价，如果是则自动完成订单
     */
    private function checkAndCompleteOrder(int $orderId, int $userId): void
    {
        $order = Order::where('id', $orderId)
            ->where('user_id', $userId)
            ->where('status', 'pending_review')
            ->first();

        if (!$order) {
            return;
        }

        // 获取订单中的所有菜品数量
        $totalOrderItems = $order->items()->count();
        
        // 获取该订单已评价的菜品数量（不区分评价状态，只要创建了评价就算）
        $reviewedItemsCount = Review::where('order_id', $orderId)
            ->where('user_id', $userId)
            ->count();

        // 如果所有菜品都已评价，则将订单状态更新为 completed
        if ($totalOrderItems > 0 && $reviewedItemsCount >= $totalOrderItems) {
            $order->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);
        }
    }
}

