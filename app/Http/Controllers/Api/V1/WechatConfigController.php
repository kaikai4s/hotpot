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

class WechatConfigController extends Controller
{
    /**
     * 获取微信配置（AppID等，用于前端发起微信登录）
     */
    public function getConfig(): JsonResponse
    {
        // 优先从配置表获取，如果没有则从config文件获取
        $appId = Configuration::getValue('wechat_app_id') ?: config('services.wechat.app_id');
        
        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'app_id' => $appId ?: null,
            ],
        ]);
    }
}


