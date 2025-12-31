<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PointRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'rule_key',
        'rule_name',
        'rule_type',
        'config',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'config' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * 获取启用的规则
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * 按类型获取规则
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('rule_type', $type);
    }
}

