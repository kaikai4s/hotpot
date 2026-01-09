<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Services;

use App\Models\MallProduct;
use App\Models\PointTransaction;
use App\Models\ProductRedemption;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PointsMallService
{
    /**
     * 商品状态常量
     */
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_SOLD_OUT = 'sold_out';

    /**
     * 商品类型常量
     */
    public const TYPE_PHYSICAL = 'physical';
    public const TYPE_EXPERIENCE = 'experience';
    public const TYPE_COUPON = 'coupon';

    /**
     * 兑换状态常量
     */
    public const REDEMPTION_PENDING = 'pending';
    public const REDEMPTION_PROCESSING = 'processing';
    public const REDEMPTION_SHIPPED = 'shipped';
    public const REDEMPTION_COMPLETED = 'completed';
    public const REDEMPTION_CANCELLED = 'cancelled';

    /**
     * 创建商品
     */
    public function createProduct(array $data): MallProduct
    {
        return MallProduct::create($data);
    }

    /**
     * 更新商品
     */
    public function updateProduct(MallProduct $product, array $data): MallProduct
    {
        $product->update($data);
        
        // 检查库存状态
        $this->checkAndUpdateSoldOutStatus($product);
        
        return $product->fresh();
    }

    /**
     * 删除商品（软删除或硬删除）
     */
    public function deleteProduct(MallProduct $product): bool
    {
        return $product->delete();
    }

    /**
     * 获取商品列表
     */
    public function getProducts(array $filters = [], ?int $perPage = null): LengthAwarePaginator
    {
        $query = MallProduct::query();

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        } else {
            // 默认只显示上架商品
            $query->where('status', self::STATUS_ACTIVE);
        }

        if (isset($filters['min_points'])) {
            $query->where('points_required', '>=', $filters['min_points']);
        }

        if (isset($filters['max_points'])) {
            $query->where('points_required', '<=', $filters['max_points']);
        }

        $sortBy = $filters['sort_by'] ?? 'sort_order';
        $sortOrder = $filters['sort_order'] ?? 'asc';
        $query->orderBy($sortBy, $sortOrder);

        $itemsPerPage = $perPage ?? $filters['per_page'] ?? 15;
        return $query->paginate($itemsPerPage);
    }

    /**
     * 获取单个商品
     */
    public function getProduct(int $productId): ?MallProduct
    {
        return MallProduct::find($productId);
    }

    /**
     * 设置商品状态
     */
    public function setProductStatus(MallProduct $product, string $status): MallProduct
    {
        $product->status = $status;
        $product->save();
        
        return $product;
    }

    /**
     * 检查并更新售罄状态
     * Property 11: 商品库存状态联动
     */
    public function checkAndUpdateSoldOutStatus(MallProduct $product): void
    {
        if ($product->stock <= 0 && $product->status === self::STATUS_ACTIVE) {
            $product->status = self::STATUS_SOLD_OUT;
            $product->save();
        }
    }

    /**
     * 检查用户是否可以兑换商品
     * Property 12: 商品兑换积分检查
     */
    public function canRedeem(User $user, MallProduct $product): array
    {
        // 检查商品状态
        if ($product->status !== self::STATUS_ACTIVE) {
            return [
                'can' => false,
                'reason' => '商品已下架或售罄',
            ];
        }

        // 检查库存
        if ($product->stock <= 0) {
            return [
                'can' => false,
                'reason' => '商品已售罄',
            ];
        }

        // 检查用户积分（从 MemberPoint 关联获取）
        $memberPoint = $user->memberPoints;
        $userPoints = $memberPoint ? $memberPoint->available_points : 0;
        if ($userPoints < $product->points_required) {
            return [
                'can' => false,
                'reason' => '积分不足',
                'points_needed' => $product->points_required - $userPoints,
            ];
        }

        // 检查兑换限制
        if ($product->per_user_limit !== null) {
            $userRedemptionCount = ProductRedemption::where('user_id', $user->id)
                ->where('product_id', $product->id)
                ->whereNotIn('status', [self::REDEMPTION_CANCELLED])
                ->count();

            if ($userRedemptionCount >= $product->per_user_limit) {
                return [
                    'can' => false,
                    'reason' => '已达到兑换上限',
                ];
            }
        }

        return [
            'can' => true,
            'reason' => null,
        ];
    }

    /**
     * 兑换商品
     * Property 13: 商品兑换原子性
     */
    public function redeemProduct(User $user, MallProduct $product, array $data = []): ProductRedemption
    {
        // 先检查是否可以兑换
        $canRedeem = $this->canRedeem($user, $product);
        if (!$canRedeem['can']) {
            throw new \Exception($canRedeem['reason']);
        }

        // 使用事务保证原子性
        return DB::transaction(function () use ($user, $product, $data) {
            // 锁定商品行
            $product = MallProduct::lockForUpdate()->find($product->id);

            // 再次检查库存（防止并发）
            if ($product->stock <= 0) {
                throw new \Exception('商品已售罄');
            }

            // 扣减库存
            $product->stock -= 1;
            $product->save();

            // 检查并更新售罄状态
            $this->checkAndUpdateSoldOutStatus($product);

            // 扣减用户积分（从 MemberPoint 关联获取并更新）
            $memberPoint = $user->memberPoints;
            if (!$memberPoint || $memberPoint->available_points < $product->points_required) {
                throw new \Exception('积分不足');
            }
            $memberPoint->available_points -= $product->points_required;
            $memberPoint->save();

            // 创建兑换记录
            $redemption = ProductRedemption::create([
                'user_id' => $user->id,
                'product_id' => $product->id,
                'points_used' => $product->points_required,
                'status' => self::REDEMPTION_PENDING,
                'shipping_address' => $data['shipping_address'] ?? null,
            ]);

            // 创建积分流水记录
            PointTransaction::create([
                'user_id' => $user->id,
                'type' => 'mall_redeem',
                'points' => -$product->points_required,
                'balance_after' => $memberPoint->available_points,
                'source_type' => 'product_redemption',
                'source_id' => $redemption->id,
                'description' => '积分商城兑换：' . $product->name,
            ]);

            return $redemption;
        });
    }

    /**
     * 获取用户兑换记录
     */
    public function getUserRedemptions(?User $user, array $filters = []): LengthAwarePaginator
    {
        $query = ProductRedemption::with(['product', 'user']);

        // 如果指定了用户，则只查询该用户的记录
        if ($user !== null) {
            $query->where('user_id', $user->id);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $query->orderBy('created_at', 'desc');

        $perPage = $filters['per_page'] ?? 15;
        return $query->paginate($perPage);
    }

    /**
     * 获取单个兑换记录
     */
    public function getRedemption(int $redemptionId): ?ProductRedemption
    {
        return ProductRedemption::with('product')->find($redemptionId);
    }

    /**
     * 更新兑换状态
     * Property 14: 兑换记录完整性
     */
    public function updateRedemptionStatus(ProductRedemption $redemption, string $status, ?string $trackingNumber = null): ProductRedemption
    {
        $redemption->status = $status;

        if ($trackingNumber !== null) {
            $redemption->tracking_number = $trackingNumber;
        }

        if ($status === self::REDEMPTION_COMPLETED) {
            $redemption->completed_at = Carbon::now();
        }

        $redemption->save();

        return $redemption;
    }

    /**
     * 获取体验类商品可用时间段
     */
    public function getAvailableTimeSlots(MallProduct $product, Carbon $date): array
    {
        if ($product->type !== self::TYPE_EXPERIENCE) {
            return [];
        }

        $config = $product->experience_config ?? [];
        $timeSlots = $config['time_slots'] ?? [];

        // 获取该日期已预约的时间段
        $bookedSlots = ProductRedemption::where('product_id', $product->id)
            ->whereDate('experience_datetime', $date)
            ->whereNotIn('status', [self::REDEMPTION_CANCELLED])
            ->pluck('experience_datetime')
            ->map(fn($dt) => Carbon::parse($dt)->format('H:i'))
            ->toArray();

        // 过滤出可用时间段
        $availableSlots = [];
        foreach ($timeSlots as $slot) {
            if (!in_array($slot, $bookedSlots)) {
                $availableSlots[] = $slot;
            }
        }

        return $availableSlots;
    }

    /**
     * 预约体验时间段
     */
    public function bookExperienceSlot(ProductRedemption $redemption, Carbon $datetime): bool
    {
        $product = $redemption->product;

        if ($product->type !== self::TYPE_EXPERIENCE) {
            return false;
        }

        // 检查时间段是否可用
        $availableSlots = $this->getAvailableTimeSlots($product, $datetime);
        $timeSlot = $datetime->format('H:i');

        if (!in_array($timeSlot, $availableSlots)) {
            return false;
        }

        $redemption->experience_datetime = $datetime;
        $redemption->experience_status = 'pending';
        $redemption->save();

        return true;
    }
}
