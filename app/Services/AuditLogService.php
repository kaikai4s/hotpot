<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuditLogService
{
    /**
     * 记录操作日志
     */
    public function log(
        string $action,
        ?string $modelType = null,
        ?int $modelId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $description = null,
        ?Request $request = null
    ): AuditLog {
        $admin = Auth::guard('admin')->user();
        
        $logData = [
            'user_type' => 'admin',
            'user_id' => $admin?->id,
            'action' => $action,
            'model_type' => $modelType,
            'model_id' => $modelId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'description' => $description,
        ];

        if ($request) {
            $logData['ip_address'] = $request->ip();
            $logData['user_agent'] = $request->userAgent();
        }

        return AuditLog::create($logData);
    }

    /**
     * 记录HTTP请求操作日志
     */
    public function logHttpRequest(
        Request $request,
        $response = null,
        ?string $action = null,
        ?string $description = null
    ): void {
        // 只记录后台管理API的操作
        if (!$request->is('api/admin/*')) {
            return;
        }

        // 排除日志查询接口本身，避免循环记录
        if ($request->is('api/admin/v1/audit-logs*')) {
            return;
        }

        $admin = Auth::guard('admin')->user();
        if (!$admin) {
            return;
        }

        // 从路由和方法推断操作类型
        $method = $request->method();
        $path = $request->path();
        
        if (!$action) {
            $action = $this->inferActionFromRoute($method, $path);
        }

        // 获取请求数据（排除敏感信息）
        $requestData = $this->sanitizeRequestData($request->all());

        // 获取响应状态
        $status = $response ? (method_exists($response, 'getStatusCode') ? $response->getStatusCode() : 200) : 200;
        
        // 构建描述
        if (!$description) {
            $description = $this->buildDescription($method, $path, $requestData, $status);
        }

        AuditLog::create([
            'user_type' => 'admin',
            'user_id' => $admin->id,
            'action' => $action,
            'model_type' => $this->inferModelTypeFromPath($path),
            'model_id' => $this->extractModelIdFromPath($path),
            'old_values' => null,
            'new_values' => $requestData,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'description' => $description,
        ]);
    }

    /**
     * 从路由推断操作类型
     */
    private function inferActionFromRoute(string $method, string $path): string
    {
        $pathParts = explode('/', $path);
        $lastPart = end($pathParts);

        // 根据HTTP方法和路径推断操作
        switch ($method) {
            case 'GET':
                if (is_numeric($lastPart)) {
                    return 'view';
                }
                return 'list';
            case 'POST':
                return 'create';
            case 'PUT':
            case 'PATCH':
                return 'update';
            case 'DELETE':
                return 'delete';
            default:
                return strtolower($method);
        }
    }

    /**
     * 从路径推断模型类型
     */
    private function inferModelTypeFromPath(string $path): ?string
    {
        $pathParts = explode('/', $path);
        
        // 查找模型名称（通常是路径的一部分）
        $modelMap = [
            'reservations' => 'App\\Models\\Reservation',
            'reviews' => 'App\\Models\\Review',
            'orders' => 'App\\Models\\Order',
            'users' => 'App\\Models\\User',
            'admins' => 'App\\Models\\Admin',
            'tables' => 'App\\Models\\Table',
            'dishes' => 'App\\Models\\Dish',
            'coupons' => 'App\\Models\\Coupon',
            'deposits' => 'App\\Models\\Reservation', // 定金关联预约
            'point-rules' => 'App\\Models\\PointRule',
            'point-statistics' => 'App\\Models\\PointStatistic',
            'point-anomalies' => null, // 异常检测不关联具体模型
        ];
        
        foreach ($pathParts as $part) {
            if (isset($modelMap[$part])) {
                return $modelMap[$part];
            }
        }

        return null;
    }

    /**
     * 从路径提取模型ID
     */
    private function extractModelIdFromPath(string $path): ?int
    {
        $pathParts = explode('/', $path);
        
        foreach ($pathParts as $part) {
            if (is_numeric($part)) {
                return (int) $part;
            }
        }

        return null;
    }

    /**
     * 清理请求数据（移除敏感信息）
     */
    private function sanitizeRequestData(array $data): array
    {
        $sensitiveFields = ['password', 'password_confirmation', 'token', 'api_key', 'secret'];
        
        foreach ($sensitiveFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = '***';
            }
        }

        return $data;
    }

    /**
     * 构建操作描述
     */
    private function buildDescription(string $method, string $path, array $requestData, int $status): string
    {
        $pathParts = explode('/', $path);
        $resource = end($pathParts);
        
        // 资源名称映射（中文）
        $resourceMap = [
            'reservations' => '预约',
            'reviews' => '评价',
            'orders' => '订单',
            'users' => '用户',
            'admins' => '管理员',
            'tables' => '桌位',
            'dishes' => '菜品',
            'coupons' => '优惠券',
            'deposits' => '定金',
            'roles' => '角色',
            'permissions' => '权限',
            'banners' => '轮播图',
            'configs' => '配置',
            'points' => '积分',
            'point-rules' => '积分规则',
            'point-statistics' => '积分统计',
            'point-anomalies' => '积分异常',
            'areas' => '区域',
            'dashboard' => '仪表盘',
            'auth' => '认证',
        ];

        $resourceName = $resourceMap[$resource] ?? $resource;
        
        // 操作类型映射
        $actionMap = [
            'GET' => '查看',
            'POST' => '创建',
            'PUT' => '更新',
            'PATCH' => '更新',
            'DELETE' => '删除',
        ];

        $action = $actionMap[$method] ?? $method;
        $statusText = $status >= 200 && $status < 300 ? '成功' : '失败';

        // 提取关键信息
        $details = [];
        
        // 提取ID
        $modelId = $this->extractModelIdFromPath($path);
        if ($modelId) {
            $details[] = "ID: {$modelId}";
        }

        // 特殊操作处理
        $specialAction = $this->getSpecialActionDescription($path, $method, $requestData);
        if ($specialAction) {
            return "{$specialAction} - {$statusText}";
        }

        // 提取关键字段信息
        $keyFields = $this->extractKeyFields($resource, $requestData);
        if (!empty($keyFields)) {
            $details = array_merge($details, $keyFields);
        }

        // 构建描述
        $description = "{$action} {$resourceName}";
        if (!empty($details)) {
            $description .= ' (' . implode(', ', $details) . ')';
        }
        $description .= " - {$statusText}";

        return $description;
    }

    /**
     * 获取特殊操作的描述
     */
    private function getSpecialActionDescription(string $path, string $method, array $requestData): ?string
    {
        $pathParts = explode('/', $path);
        
        // 预约相关操作
        if (str_contains($path, 'reservations')) {
            if (str_contains($path, 'confirm')) {
                $id = end($pathParts);
                return "确认预约 (ID: {$id})";
            }
            if (str_contains($path, 'cancel')) {
                $id = end($pathParts);
                return "取消预约 (ID: {$id})";
            }
        }

        // 评价相关操作
        if (str_contains($path, 'reviews')) {
            if (str_contains($path, 'approve')) {
                $id = $this->extractModelIdFromPath($path);
                return "审核通过评价 (ID: {$id})";
            }
            if (str_contains($path, 'reply')) {
                $id = $this->extractModelIdFromPath($path);
                return "回复评价 (ID: {$id})";
            }
            if (str_contains($path, 'adopt')) {
                $id = $this->extractModelIdFromPath($path);
                return "采纳评价 (ID: {$id})";
            }
            if (str_contains($path, 'tracking')) {
                $id = $this->extractModelIdFromPath($path);
                $status = $requestData['tracking_status'] ?? '';
                $statusText = match($status) {
                    'pending' => '待处理',
                    'in_progress' => '进行中',
                    'completed' => '已完成',
                    'cancelled' => '已取消',
                    default => $status,
                };
                return "更新评价追踪状态 (ID: {$id}, 状态: {$statusText})";
            }
        }

        // 订单相关操作
        if (str_contains($path, 'orders')) {
            if (str_contains($path, 'status')) {
                $id = $this->extractModelIdFromPath($path);
                $status = $requestData['status'] ?? '';
                return "更新订单状态 (ID: {$id}, 状态: {$status})";
            }
            if (str_contains($path, 'cancel')) {
                $id = $this->extractModelIdFromPath($path);
                return "取消订单 (ID: {$id})";
            }
            if (str_contains($path, 'complete')) {
                $id = $this->extractModelIdFromPath($path);
                return "完成订单 (ID: {$id})";
            }
        }

        // 定金相关操作
        if (str_contains($path, 'deposits')) {
            if (str_contains($path, 'refund')) {
                $id = $this->extractModelIdFromPath($path);
                return "退还定金 (预约ID: {$id})";
            }
        }

        // 积分相关操作
        if (str_contains($path, 'points')) {
            if (str_contains($path, 'adjust')) {
                $userId = $this->extractModelIdFromPath($path);
                $points = $requestData['points'] ?? 0;
                $type = $points >= 0 ? '增加' : '扣除';
                $reason = $requestData['reason'] ?? '';
                $desc = "调整用户积分 (用户ID: {$userId}, {$type} {$points} 积分";
                if ($reason) {
                    $desc .= ", 原因: {$reason}";
                }
                $desc .= ')';
                return $desc;
            }
        }

        // 桌位位置更新
        if (str_contains($path, 'tables') && str_contains($path, 'positions')) {
            return "批量更新桌位位置";
        }

        // 区域批量更新
        if (str_contains($path, 'areas') && str_contains($path, 'batch-update')) {
            return "批量更新区域配置";
        }

        // 配置批量更新
        if (str_contains($path, 'configs') && str_contains($path, 'batch-update')) {
            return "批量更新系统配置";
        }

        // 轮播图排序更新
        if (str_contains($path, 'banners') && str_contains($path, 'update-order')) {
            return "更新轮播图排序";
        }

        // 登录/登出
        if (str_contains($path, 'auth')) {
            if (str_contains($path, 'login')) {
                $username = $requestData['username'] ?? '';
                return "管理员登录 (用户名: {$username})";
            }
            if (str_contains($path, 'logout')) {
                return "管理员登出";
            }
        }

        return null;
    }

    /**
     * 提取关键字段信息
     */
    private function extractKeyFields(string $resource, array $requestData): array
    {
        $fields = [];

        // 根据资源类型提取关键字段
        switch ($resource) {
            case 'reservations':
                if (isset($requestData['date'])) {
                    $fields[] = "日期: {$requestData['date']}";
                }
                if (isset($requestData['time_slot'])) {
                    $fields[] = "时段: {$requestData['time_slot']}";
                }
                if (isset($requestData['table_id'])) {
                    $fields[] = "桌位ID: {$requestData['table_id']}";
                }
                break;

            case 'reviews':
                if (isset($requestData['rating'])) {
                    $fields[] = "评分: {$requestData['rating']}星";
                }
                if (isset($requestData['status'])) {
                    $statusMap = [
                        'pending' => '待审核',
                        'approved' => '已通过',
                        'rejected' => '已拒绝',
                    ];
                    $statusText = $statusMap[$requestData['status']] ?? $requestData['status'];
                    $fields[] = "状态: {$statusText}";
                }
                break;

            case 'orders':
                if (isset($requestData['status'])) {
                    $fields[] = "状态: {$requestData['status']}";
                }
                if (isset($requestData['total_amount'])) {
                    $fields[] = "金额: ¥{$requestData['total_amount']}";
                }
                break;

            case 'users':
            case 'admins':
                if (isset($requestData['name'])) {
                    $fields[] = "姓名: {$requestData['name']}";
                }
                if (isset($requestData['username'])) {
                    $fields[] = "用户名: {$requestData['username']}";
                }
                break;

            case 'dishes':
                if (isset($requestData['name'])) {
                    $fields[] = "名称: {$requestData['name']}";
                }
                if (isset($requestData['price'])) {
                    $fields[] = "价格: ¥{$requestData['price']}";
                }
                break;

            case 'tables':
                if (isset($requestData['name'])) {
                    $fields[] = "名称: {$requestData['name']}";
                }
                if (isset($requestData['capacity'])) {
                    $fields[] = "容量: {$requestData['capacity']}人";
                }
                break;

            case 'coupons':
                if (isset($requestData['name'])) {
                    $fields[] = "名称: {$requestData['name']}";
                }
                if (isset($requestData['discount_type'])) {
                    $typeMap = [
                        'fixed' => '固定金额',
                        'percentage' => '百分比',
                    ];
                    $typeText = $typeMap[$requestData['discount_type']] ?? $requestData['discount_type'];
                    $fields[] = "类型: {$typeText}";
                }
                break;

            case 'roles':
                if (isset($requestData['name'])) {
                    $fields[] = "角色名: {$requestData['name']}";
                }
                break;

            case 'point-rules':
                if (isset($requestData['name'])) {
                    $fields[] = "规则名: {$requestData['name']}";
                }
                if (isset($requestData['points'])) {
                    $fields[] = "积分: {$requestData['points']}";
                }
                break;
        }

        return $fields;
    }
}

