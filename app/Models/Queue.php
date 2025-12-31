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

class Queue extends Model
{
    use HasFactory;

    protected $table = 'queue';

    protected $fillable = [
        'queue_number',
        'user_id',
        'guest_count',
        'table_type',
        'position',
        'status',
        'joined_at',
        'called_at',
        'seated_at',
    ];

    protected $casts = [
        'guest_count' => 'integer',
        'position' => 'integer',
        'joined_at' => 'datetime',
        'called_at' => 'datetime',
        'seated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

