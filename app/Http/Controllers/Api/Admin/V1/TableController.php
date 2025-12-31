<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin\V1;

use App\Http\Controllers\Controller;
use App\Models\Table;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TableController extends Controller
{
    /**
     * 获取所有桌位列表
     */
    public function index(): JsonResponse
    {
        $tables = Table::orderBy('name')->get();

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'tables' => $tables,
            ],
        ]);
    }

    /**
     * 更新桌位信息（包括位置）
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $table = Table::findOrFail($id);
        
        $request->validate([
            'name' => 'sometimes|string|max:20|unique:tables,name,' . $id,
            'capacity' => 'sometimes|integer|min:1|max:20',
            'type' => 'sometimes|in:window,corner,center',
            'status' => 'sometimes|in:available,reserved,occupied,maintenance',
            'position_x' => 'nullable|integer|min:0',
            'position_y' => 'nullable|integer|min:0',
            'default_position_x' => 'nullable|integer|min:0',
            'default_position_y' => 'nullable|integer|min:0',
        ]);

        $updateData = $request->only([
            'name',
            'capacity',
            'type',
            'status',
            'position_x',
            'position_y',
            'default_position_x',
            'default_position_y',
        ]);

        // 如果状态变更为 occupied，记录使用开始时间
        if ($request->has('status')) {
            $newStatus = $request->input('status');
            if ($newStatus === 'occupied' && $table->status !== 'occupied') {
                // 状态变为使用中，记录开始时间
                $updateData['occupied_at'] = now();
            } elseif ($newStatus !== 'occupied' && $table->status === 'occupied') {
                // 状态从使用中变为其他状态，清除使用时间
                $updateData['occupied_at'] = null;
            }
        }

        $table->update($updateData);

        return response()->json([
            'code' => 200,
            'message' => '桌位更新成功',
            'data' => [
                'table' => $table->fresh(),
            ],
        ]);
    }

    /**
     * 批量更新桌位位置
     */
    public function updatePositions(Request $request): JsonResponse
    {
        $request->validate([
            'tables' => 'required|array',
            'tables.*.id' => 'required|integer|exists:tables,id',
            'tables.*.position_x' => 'nullable|integer|min:0',
            'tables.*.position_y' => 'nullable|integer|min:0',
        ]);

        foreach ($request->input('tables') as $tableData) {
            Table::where('id', $tableData['id'])->update([
                'position_x' => $tableData['position_x'] ?? null,
                'position_y' => $tableData['position_y'] ?? null,
            ]);
        }

        return response()->json([
            'code' => 200,
            'message' => '桌位位置更新成功',
        ]);
    }

    /**
     * 获取单个桌位详情
     */
    public function show(int $id): JsonResponse
    {
        $table = Table::findOrFail($id);

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'table' => $table,
            ],
        ]);
    }

    /**
     * 创建桌位
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:20|unique:tables,name',
            'capacity' => 'required|integer|min:1|max:20',
            'type' => 'required|in:window,corner,center',
            'status' => 'sometimes|in:available,reserved,occupied,maintenance',
            'position_x' => 'nullable|integer|min:0',
            'position_y' => 'nullable|integer|min:0',
        ]);

        $table = Table::create([
            'name' => $request->input('name'),
            'capacity' => $request->input('capacity'),
            'type' => $request->input('type'),
            'status' => $request->input('status', 'available'),
            'position_x' => $request->input('position_x'),
            'position_y' => $request->input('position_y'),
        ]);

        return response()->json([
            'code' => 201,
            'message' => '桌位创建成功',
            'data' => [
                'table' => $table,
            ],
        ], 201);
    }

    /**
     * 删除桌位
     */
    public function destroy(int $id): JsonResponse
    {
        $table = Table::findOrFail($id);
        
        // 检查是否有关联的预约
        if ($table->reservations()->exists()) {
            return response()->json([
                'code' => 400,
                'message' => '该桌位存在关联的预约记录，无法删除',
            ], 400);
        }

        $table->delete();

        return response()->json([
            'code' => 200,
            'message' => '桌位删除成功',
        ]);
    }
}

