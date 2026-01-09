<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BirthdayPrivilegeLog extends Model
{
    public $timestamps = false;

    protected $table = 'birthday_privileges_log';

    protected $fillable = [
        'user_id',
        'year',
        'privilege_type',
        'reference_id',
        'issued_at',
    ];

    protected $casts = [
        'year' => 'integer',
        'reference_id' => 'integer',
        'issued_at' => 'datetime',
    ];

    public const TYPE_COUPON = 'coupon';
    public const TYPE_DESSERT = 'dessert';
    public const TYPE_DOUBLE_POINTS = 'double_points';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
