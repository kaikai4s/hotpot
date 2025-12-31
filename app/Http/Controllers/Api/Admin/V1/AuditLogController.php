<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin\V1;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    /**
     * 获取操作日志列表
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'nullable|integer',
            'action' => 'nullable|string|max:50',
            'model_type' => 'nullable|string|max:100',
            'model_id' => 'nullable|integer',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'page' => 'nullable|integer|min:1',
            'page_size' => 'nullable|integer|min:1|max:100',
        ]);

        $query = AuditLog::where('user_type', 'admin');

        // 用户ID筛选
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        // 操作类型筛选
        if ($request->filled('action')) {
            $query->where('action', $request->input('action'));
        }

        // 模型类型筛选
        if ($request->filled('model_type')) {
            $query->where('model_type', $request->input('model_type'));
        }

        // 模型ID筛选
        if ($request->filled('model_id')) {
            $query->where('model_id', $request->input('model_id'));
        }

        // 日期范围筛选
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        $page = $request->input('page', 1);
        $pageSize = $request->input('page_size', 20);
        $logs = $query->orderBy('created_at', 'desc')
            ->paginate($pageSize, ['*'], 'page', $page);

        // 加载关联的管理员信息
        $logs->load('admin');

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'logs' => $logs->items(),
                'pagination' => [
                    'current_page' => $logs->currentPage(),
                    'total_pages' => $logs->lastPage(),
                    'total_count' => $logs->total(),
                    'page_size' => $logs->perPage(),
                ],
            ],
        ]);
    }

    /**
     * 获取操作日志详情
     */
    public function show(int $id): JsonResponse
    {
        $log = AuditLog::with('admin')->findOrFail($id);

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => $log,
        ]);
    }

    /**
     * 获取操作类型统计
     */
    public function statistics(Request $request): JsonResponse
    {
        $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        $query = AuditLog::where('user_type', 'admin');

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        // 按操作类型统计
        $actionStats = $query->selectRaw('action, COUNT(*) as count')
            ->groupBy('action')
            ->orderByDesc('count')
            ->get();

        // 按管理员统计
        $adminStats = AuditLog::where('user_type', 'admin')
            ->when($request->filled('date_from'), function ($q) use ($request) {
                $q->whereDate('created_at', '>=', $request->input('date_from'));
            })
            ->when($request->filled('date_to'), function ($q) use ($request) {
                $q->whereDate('created_at', '<=', $request->input('date_to'));
            })
            ->selectRaw('user_id, COUNT(*) as count')
            ->groupBy('user_id')
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->load('admin');

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'action_statistics' => $actionStats,
                'admin_statistics' => $adminStats,
            ],
        ]);
    }
}

