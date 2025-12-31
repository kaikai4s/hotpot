<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RestaurantArea extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'boundaries',
        'color',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'boundaries' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];
}

