<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin\V1;

use App\Http\Controllers\Controller;
use App\Models\PointLevel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PointLevelController extends Controller
{
    /**
     * 获取段位列表
     */
    public function index(Request $request)
    {
        $query = PointLevel::query();

        // 搜索
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // 是否启用筛选
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // 排序
        $sortBy = $request->input('sort_by', 'sort_order');
        $sortOrder = $request->input('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);
        $query->orderBy('min_points', 'asc');

        // 分页
        $perPage = $request->input('per_page', 15);
        $levels = $query->paginate($perPage);

        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => [
                'levels' => $levels->items(),
                'pagination' => [
                    'total' => $levels->total(),
                    'per_page' => $levels->perPage(),
                    'current_page' => $levels->currentPage(),
                    'last_page' => $levels->lastPage(),
                ],
            ],
        ]);
    }

    /**
     * 获取单个段位详情
     */
    public function show($id)
    {
        $level = PointLevel::find($id);

        if (!$level) {
            return response()->json([
                'code' => 404,
                'message' => '段位不存在',
            ], 404);
        }

        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => [
                'level' => $level,
            ],
        ]);
    }

    /**
     * 创建段位
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:50',
            'code' => 'required|string|max:50|unique:point_levels,code',
            'min_points' => 'required|integer|min:0',
            'discount_type' => 'required|in:none,percentage,fixed',
            'discount_value' => 'required|numeric|min:0',
            'max_discount_amount' => 'nullable|numeric|min:0',
            'min_order_amount' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'description' => 'nullable|string|max:500',
            'icon' => 'nullable|string|max:100',
            'color' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'code' => 400,
                'message' => '验证失败',
                'errors' => $validator->errors(),
            ], 400);
        }

        // 验证百分比折扣值范围
        if ($request->input('discount_type') === 'percentage' && $request->input('discount_value') > 100) {
            return response()->json([
                'code' => 400,
                'message' => '百分比折扣值不能超过100',
            ], 400);
        }

        try {
            // 准备创建数据，处理空字符串转换为 null
            $createData = $request->only([
                'name',
                'code',
                'min_points',
                'discount_type',
                'discount_value',
                'max_discount_amount',
                'min_order_amount',
                'is_active',
                'sort_order',
                'description',
                'icon',
                'color',
            ]);
            
            // 将空字符串转换为 null（对于 nullable 字段）
            if (isset($createData['description']) && $createData['description'] === '') {
                $createData['description'] = null;
            }
            if (isset($createData['icon']) && $createData['icon'] === '') {
                $createData['icon'] = null;
            }
            if (isset($createData['color']) && $createData['color'] === '') {
                $createData['color'] = null;
            }
            if (isset($createData['max_discount_amount']) && $createData['max_discount_amount'] === '') {
                $createData['max_discount_amount'] = null;
            }
            
            $level = PointLevel::create($createData);

            return response()->json([
                'code' => 200,
                'message' => '创建成功',
                'data' => [
                    'level' => $level,
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => '创建失败：' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 更新段位
     */
    public function update(Request $request, $id)
    {
        $level = PointLevel::find($id);

        if (!$level) {
            return response()->json([
                'code' => 404,
                'message' => '段位不存在',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:50',
            'code' => 'sometimes|required|string|max:50|unique:point_levels,code,' . $id,
            'min_points' => 'sometimes|required|integer|min:0',
            'discount_type' => 'sometimes|required|in:none,percentage,fixed',
            'discount_value' => 'sometimes|required|numeric|min:0',
            'max_discount_amount' => 'nullable|numeric|min:0',
            'min_order_amount' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'description' => 'nullable|string|max:500',
            'icon' => 'nullable|string|max:100',
            'color' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'code' => 400,
                'message' => '验证失败',
                'errors' => $validator->errors(),
            ], 400);
        }

        // 验证百分比折扣值范围
        if ($request->has('discount_type') && $request->input('discount_type') === 'percentage' 
            && $request->has('discount_value') && $request->input('discount_value') > 100) {
            return response()->json([
                'code' => 400,
                'message' => '百分比折扣值不能超过100',
            ], 400);
        }

        try {
            // 准备更新数据，处理空字符串转换为 null
            $updateData = $request->only([
                'name',
                'code',
                'min_points',
                'discount_type',
                'discount_value',
                'max_discount_amount',
                'min_order_amount',
                'is_active',
                'sort_order',
                'description',
                'icon',
                'color',
            ]);
            
            // 将空字符串转换为 null（对于 nullable 字段）
            if (isset($updateData['description']) && $updateData['description'] === '') {
                $updateData['description'] = null;
            }
            if (isset($updateData['icon']) && $updateData['icon'] === '') {
                $updateData['icon'] = null;
            }
            if (isset($updateData['color']) && $updateData['color'] === '') {
                $updateData['color'] = null;
            }
            if (isset($updateData['max_discount_amount']) && $updateData['max_discount_amount'] === '') {
                $updateData['max_discount_amount'] = null;
            }
            
            $level->update($updateData);

            return response()->json([
                'code' => 200,
                'message' => '更新成功',
                'data' => [
                    'level' => $level->fresh(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => '更新失败：' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 删除段位
     */
    public function destroy($id)
    {
        $level = PointLevel::find($id);

        if (!$level) {
            return response()->json([
                'code' => 404,
                'message' => '段位不存在',
            ], 404);
        }

        // 检查是否有用户使用该段位
        $userCount = DB::table('member_points')
            ->where('level', $level->code)
            ->count();

        if ($userCount > 0) {
            return response()->json([
                'code' => 400,
                'message' => "该段位有 {$userCount} 个用户正在使用，无法删除",
            ], 400);
        }

        try {
            $level->delete();

            return response()->json([
                'code' => 200,
                'message' => '删除成功',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => '删除失败：' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 切换启用状态
     */
    public function toggleActive($id)
    {
        $level = PointLevel::find($id);

        if (!$level) {
            return response()->json([
                'code' => 404,
                'message' => '段位不存在',
            ], 404);
        }

        $level->is_active = !$level->is_active;
        $level->save();

        return response()->json([
            'code' => 200,
            'message' => '操作成功',
            'data' => [
                'level' => $level,
            ],
        ]);
    }

    /**
     * 获取所有启用的段位（用于下拉选择）
     */
    public function getActiveLevels()
    {
        $levels = PointLevel::where('is_active', true)
            ->orderBy('sort_order', 'asc')
            ->orderBy('min_points', 'asc')
            ->get();

        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => [
                'levels' => $levels,
            ],
        ]);
    }

    /**
     * 批量更新所有用户的段位
     * 
     * 重要：段位判断基于 total_points（总积分），而不是 available_points（可用积分）
     * 总积分是用户累计获得的所有积分，不会因为积分兑换而减少
     * 可用积分会因兑换、过期等原因减少，但不影响段位判断
     */
    public function updateAllUserLevels()
    {
        try {
            $memberPoints = \App\Models\MemberPoint::all();
            $total = $memberPoints->count();
            $updated = 0;

            foreach ($memberPoints as $memberPoint) {
                // 使用总积分来判断段位，而不是可用积分
                // 这样即使积分被兑换或过期，段位也不会降低
                $totalPoints = $memberPoint->total_points;
                
                // 获取对应的段位
                $newLevel = PointLevel::getLevelByPoints($totalPoints);
                $newLevelCode = $newLevel ? $newLevel->code : 'bronze';

                // 如果段位不同，则更新
                if ($memberPoint->level !== $newLevelCode) {
                    $memberPoint->update(['level' => $newLevelCode]);
                    $updated++;
                }
            }

            return response()->json([
                'code' => 200,
                'message' => "批量更新完成，共更新 {$updated} 个用户的段位（共 {$total} 个用户）。段位判断基于总积分（total_points），不受积分兑换影响。",
                'data' => [
                    'total' => $total,
                    'updated' => $updated,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => '批量更新失败：' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 获取所有已使用的图标列表（用于下拉选择）
     */
    public function getUsedIcons()
    {
        $icons = PointLevel::whereNotNull('icon')
            ->where('icon', '!=', '')
            ->distinct()
            ->pluck('icon')
            ->filter()
            ->values()
            ->toArray();

        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => [
                'icons' => $icons,
            ],
        ]);
    }
}

