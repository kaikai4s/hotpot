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

class MemberPoint extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'total_points',
        'available_points',
        'frozen_points',
        'level',
    ];

    protected $casts = [
        'total_points' => 'integer',
        'available_points' => 'integer',
        'frozen_points' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 获取段位信息
     */
    public function pointLevel(): BelongsTo
    {
        return $this->belongsTo(PointLevel::class, 'level', 'code');
    }
}

