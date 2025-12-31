<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\JsonResponse;

class BannerController extends Controller
{
    /**
     * 获取轮播图列表（前台）
     */
    public function index(): JsonResponse
    {
        $banners = Banner::active()
            ->ordered()
            ->get();

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'banners' => $banners,
            ],
        ]);
    }
}
