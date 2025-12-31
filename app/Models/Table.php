<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Table extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'capacity',
        'type',
        'position_x',
        'position_y',
        'default_position_x',
        'default_position_y',
        'status',
        'occupied_at',
    ];

    protected $casts = [
        'capacity' => 'integer',
        'position_x' => 'integer',
        'position_y' => 'integer',
        'default_position_x' => 'integer',
        'default_position_y' => 'integer',
        'occupied_at' => 'datetime',
    ];

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}

