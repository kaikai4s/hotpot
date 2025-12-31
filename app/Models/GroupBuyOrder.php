<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupBuyOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_buy_id',
        'user_id',
        'order_id',
        'quantity',
        'total_price',
        'status',
        'paid_at',
        'used_at',
        'expires_at',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'total_price' => 'decimal:2',
        'paid_at' => 'datetime',
        'used_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * 所属团购
     */
    public function groupBuy(): BelongsTo
    {
        return $this->belongsTo(GroupBuy::class);
    }

    /**
     * 用户
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 关联订单
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * 检查是否可以使用
     */
    public function canUse(): bool
    {
        if ($this->status !== 'paid') {
            return false;
        }

        if ($this->expires_at && now()->greaterThan($this->expires_at)) {
            return false;
        }

        return true;
    }
}
