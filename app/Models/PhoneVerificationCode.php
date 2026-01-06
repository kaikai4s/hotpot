<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhoneVerificationCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'phone',
        'code',
        'type',
        'is_used',
        'expires_at',
        'used_at',
        'ip_address',
    ];

    protected $casts = [
        'is_used' => 'boolean',
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    /**
     * 检查验证码是否有效
     */
    public function isValid(): bool
    {
        return !$this->is_used && $this->expires_at->isFuture();
    }

    /**
     * 标记为已使用
     */
    public function markAsUsed(): void
    {
        $this->is_used = true;
        $this->used_at = now();
        $this->save();
    }
}
