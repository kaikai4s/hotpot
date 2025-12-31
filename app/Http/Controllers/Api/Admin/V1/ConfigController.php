<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin\V1;

use App\Http\Controllers\Controller;
use App\Models\Configuration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConfigController extends Controller
{
    /**
     * 获取所有配置（按分组）
     */
    public function index(Request $request): JsonResponse
    {
        $group = $request->input('group');
        
        $query = Configuration::orderBy('sort_order')->orderBy('id');
        
        if ($group) {
            $query->where('group', $group);
        }
        
        $configs = $query->get();
        
        // 按分组组织
        $grouped = $configs->groupBy('group')->map(function ($items) {
            return $items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'key' => $item->key,
                    'value' => $item->value,
                    'type' => $item->type,
                    'group' => $item->group,
                    'label' => $item->label,
                    'description' => $item->description,
                    'sort_order' => $item->sort_order,
                    'is_public' => $item->is_public,
                ];
            })->values();
        });

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'configs' => $configs,
                'grouped' => $grouped,
            ],
        ]);
    }

    /**
     * 获取单个配置
     */
    public function show(string $key): JsonResponse
    {
        $config = Configuration::where('key', $key)->firstOrFail();

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'config' => $config,
            ],
        ]);
    }

    /**
     * 批量更新配置
     */
    public function batchUpdate(Request $request): JsonResponse
    {
        $request->validate([
            'configs' => 'required|array',
            'configs.*.key' => 'required|string',
            'configs.*.value' => 'nullable',
        ]);

        foreach ($request->input('configs') as $item) {
            $config = Configuration::where('key', $item['key'])->first();
            
            if ($config) {
                // 使用模型的setValueAttribute方法，会自动根据type转换
                $config->value = $item['value'] ?? null;
                $config->save();
            }
        }

        return response()->json([
            'code' => 200,
            'message' => '配置更新成功',
        ]);
    }

    /**
     * 更新单个配置
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $config = Configuration::findOrFail($id);

        $request->validate([
            'value' => 'nullable',
            'label' => 'sometimes|string|max:100',
            'description' => 'nullable|string',
            'is_public' => 'sometimes|boolean',
        ]);

        $config->update($request->only([
            'value',
            'label',
            'description',
            'is_public',
        ]));

        return response()->json([
            'code' => 200,
            'message' => '配置更新成功',
            'data' => [
                'config' => $config,
            ],
        ]);
    }
}
