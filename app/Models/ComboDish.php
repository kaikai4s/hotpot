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

class ComboDish extends Model
{
    use HasFactory;

    protected $fillable = [
        'combo_id',
        'dish_id',
        'quantity',
        'sort_order',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'sort_order' => 'integer',
    ];

    public function combo(): BelongsTo
    {
        return $this->belongsTo(Combo::class);
    }

    public function dish(): BelongsTo
    {
        return $this->belongsTo(Dish::class);
    }
}

