<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Table;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TableController extends Controller
{
    /**
     * 获取可用桌位列表（用于点餐时选择桌位）
     */
    public function getAvailableTables(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        // 获取状态为 available 或 reserved 的桌位
        // 同时获取当前用户正在使用的 occupied 桌位（用于加菜）
        $query = Table::where(function ($q) use ($user) {
            // 总是包含 available 和 reserved 状态的桌位
            $q->whereIn('status', ['available', 'reserved']);
            
            // 如果用户已登录，也包含该用户正在使用的 occupied 桌位
            if ($user) {
                $q->orWhere(function ($subQ) use ($user) {
                    // 如果桌位是 occupied 状态，且使用人是当前用户，也可以选择（用于加菜）
                    $subQ->where('status', 'occupied')
                         ->where('occupied_by_user_id', $user->id);
                });
            }
        });
        
        $tables = $query->with('occupiedByUser:id,nickname')
            ->orderBy('name')
            ->get();

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'tables' => $tables,
            ],
        ]);
    }
    
    /**
     * 加入团队点餐
     */
    public function joinTeam(Request $request): JsonResponse
    {
        $request->validate([
            'team_code' => 'required|string|size:10',
        ]);
        
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'code' => 401,
                'message' => '未登录',
            ], 401);
        }
        
        $table = Table::where('team_code', $request->input('team_code'))
            ->where('status', 'occupied')
            ->first();
            
        if (!$table) {
            return response()->json([
                'code' => 404,
                'message' => '团队码无效或桌位不可用',
            ], 404);
        }
        
        return response()->json([
            'code' => 200,
            'message' => '成功加入团队',
            'data' => [
                'table' => $table->load('occupiedByUser:id,nickname'),
            ],
        ]);
    }
}

