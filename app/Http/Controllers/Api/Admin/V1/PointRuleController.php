<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin\V1;

use App\Http\Controllers\Controller;
use App\Models\PointRule;
use App\Services\PointRuleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PointRuleController extends Controller
{
    public function __construct(
        private PointRuleService $ruleService
    ) {
    }

    /**
     * 获取积分规则列表
     */
    public function index(Request $request): JsonResponse
    {
        $query = PointRule::query();

        // 按类型筛选
        if ($request->has('rule_type')) {
            $query->where('rule_type', $request->input('rule_type'));
        }

        // 按状态筛选
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $rules = $query->orderBy('sort_order', 'asc')->get();

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'rules' => $rules,
            ],
        ]);
    }

    /**
     * 获取积分规则详情
     */
    public function show(int $id): JsonResponse
    {
        $rule = PointRule::findOrFail($id);

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'rule' => $rule,
            ],
        ]);
    }

    /**
     * 创建积分规则
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'rule_key' => 'required|string|max:50|unique:point_rules,rule_key',
            'rule_name' => 'required|string|max:100',
            'rule_type' => ['required', Rule::in(['earn', 'use', 'expire'])],
            'config' => 'required|array',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        $rule = PointRule::create($request->all());

        // 清除缓存
        $this->ruleService->clearCache($rule->rule_key);

        return response()->json([
            'code' => 201,
            'message' => '积分规则创建成功',
            'data' => [
                'rule' => $rule,
            ],
        ], 201);
    }

    /**
     * 更新积分规则
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $rule = PointRule::findOrFail($id);

        $request->validate([
            'rule_key' => ['sometimes', 'string', 'max:50', Rule::unique('point_rules', 'rule_key')->ignore($id)],
            'rule_name' => 'sometimes|string|max:100',
            'rule_type' => ['sometimes', Rule::in(['earn', 'use', 'expire'])],
            'config' => 'sometimes|array',
            'is_active' => 'sometimes|boolean',
            'sort_order' => 'sometimes|integer|min:0',
        ]);

        $oldKey = $rule->rule_key;
        $rule->update($request->all());

        // 清除缓存
        $this->ruleService->clearCache($oldKey);
        if ($rule->rule_key !== $oldKey) {
            $this->ruleService->clearCache($rule->rule_key);
        }

        return response()->json([
            'code' => 200,
            'message' => '积分规则更新成功',
            'data' => [
                'rule' => $rule,
            ],
        ]);
    }

    /**
     * 删除积分规则
     */
    public function destroy(int $id): JsonResponse
    {
        $rule = PointRule::findOrFail($id);
        $ruleKey = $rule->rule_key;
        
        $rule->delete();

        // 清除缓存
        $this->ruleService->clearCache($ruleKey);

        return response()->json([
            'code' => 200,
            'message' => '积分规则删除成功',
        ]);
    }
}

