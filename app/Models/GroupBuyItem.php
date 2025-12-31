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

class GroupBuyItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_buy_id',
        'dish_id',
        'quantity',
        'sort_order',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'sort_order' => 'integer',
    ];

    /**
     * 所属团购
     */
    public function groupBuy(): BelongsTo
    {
        return $this->belongsTo(GroupBuy::class);
    }

    /**
     * 菜品
     */
    public function dish(): BelongsTo
    {
        return $this->belongsTo(Dish::class);
    }
}
