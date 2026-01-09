<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserBirthday extends Model
{
    protected $fillable = [
        'user_id',
        'birthday',
        'last_modified_at',
        'last_modified_year',
    ];

    protected $casts = [
        'birthday' => 'date',
        'last_modified_at' => 'datetime',
        'last_modified_year' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
