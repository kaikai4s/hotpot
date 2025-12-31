<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PointStatistic extends Model
{
    use HasFactory;

    protected $fillable = [
        'stat_date',
        'total_earned',
        'total_redeemed',
        'total_expired',
        'active_users',
    ];

    protected $casts = [
        'stat_date' => 'date',
        'total_earned' => 'integer',
        'total_redeemed' => 'integer',
        'total_expired' => 'integer',
        'active_users' => 'integer',
    ];
}

