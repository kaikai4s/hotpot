<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Configuration extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'label',
        'description',
        'sort_order',
        'is_public',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * 获取配置值（根据类型转换）
     */
    public function getValueAttribute($value)
    {
        if ($value === null) {
            return null;
        }

        return match ($this->type) {
            'integer' => (int) $value,
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode($value, true),
            default => $value,
        };
    }

    /**
     * 设置配置值（根据类型转换）
     */
    public function setValueAttribute($value)
    {
        if ($value === null) {
            $this->attributes['value'] = null;
            return;
        }

        $this->attributes['value'] = match ($this->type) {
            'integer' => (string) $value,
            'boolean' => $value ? '1' : '0',
            'json' => json_encode($value),
            default => (string) $value,
        };
    }

    /**
     * 获取配置值（静态方法）
     */
    public static function getValue(string $key, $default = null)
    {
        $config = self::where('key', $key)->first();
        return $config ? $config->value : $default;
    }

    /**
     * 设置配置值（静态方法）
     */
    public static function setValue(string $key, $value, string $type = 'string'): void
    {
        self::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'type' => $type,
            ]
        );
    }
}
