<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Configuration;
use Illuminate\Http\JsonResponse;

class ConfigController extends Controller
{
    /**
     * 获取公开配置
     */
    public function getPublicConfig(string $key): JsonResponse
    {
        $config = Configuration::where('key', $key)
            ->where('is_public', true)
            ->firstOrFail();

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'key' => $config->key,
                'value' => $config->value,
            ],
        ]);
    }
}
