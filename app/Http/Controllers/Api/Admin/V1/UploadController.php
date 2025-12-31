<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UploadController extends Controller
{
    /**
     * 上传图片
     */
    public function uploadImage(Request $request): JsonResponse
    {
        try {
            // 在 API 路由中，hasFile() 可能不可靠，直接使用 allFiles() 获取
            $allFiles = $request->allFiles();
            
            // 调试信息
            \Log::info('上传请求接收', [
                'content_type' => $request->header('Content-Type'),
                'method' => $request->method(),
                'all_files_keys' => array_keys($allFiles),
                'has_file_image' => $request->hasFile('image'),
                'file_exists' => isset($allFiles['image']),
                'php_upload_max_filesize' => ini_get('upload_max_filesize'),
                'php_post_max_size' => ini_get('post_max_size'),
            ]);

            // 检查文件是否存在
            if (!isset($allFiles['image'])) {
                // 尝试从 file() 方法获取
                $file = $request->file('image');
                if (!$file) {
                    return response()->json([
                        'code' => 400,
                        'message' => '请选择要上传的图片文件',
                    ], 400);
                }
            } else {
                $file = $allFiles['image'];
            }
            
            // 检查文件是否有效
            if (!$file->isValid()) {
                $errorCode = $file->getError();
                $errorMsg = $file->getErrorMessage();
                
                \Log::error('文件无效', [
                    'error' => $errorMsg,
                    'error_code' => $errorCode,
                    'php_upload_max_filesize' => ini_get('upload_max_filesize'),
                    'php_post_max_size' => ini_get('post_max_size'),
                ]);
                
                // PHP 错误代码：UPLOAD_ERR_INI_SIZE (1) 或 UPLOAD_ERR_FORM_SIZE (2) 表示文件太大
                if ($errorCode === UPLOAD_ERR_INI_SIZE || $errorCode === UPLOAD_ERR_FORM_SIZE) {
                    $maxSize = ini_get('upload_max_filesize');
                    return response()->json([
                        'code' => 422,
                        'message' => "文件大小超过服务器限制（当前限制：{$maxSize}）。请联系管理员调整 PHP 配置。",
                    ], 422);
                }
                
                return response()->json([
                    'code' => 400,
                    'message' => '文件上传失败：' . $errorMsg,
                ], 400);
            }
            
            // 手动验证文件类型和大小
            $allowedMimes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'image/avif'];
            $mimeType = $file->getMimeType();
            
            if (!in_array($mimeType, $allowedMimes)) {
                return response()->json([
                    'code' => 422,
                    'message' => '不支持的文件类型：' . $mimeType . '，仅支持 jpg、png、gif、webp、avif',
                ], 422);
            }
            
            // 检查文件大小（5MB = 5120 KB）
            $maxSize = 5120 * 1024; // 5MB in bytes
            if ($file->getSize() > $maxSize) {
                return response()->json([
                    'code' => 422,
                    'message' => '文件大小超过限制（最大5MB）',
                ], 422);
            }

            $extension = $file->getClientOriginalExtension();
            $filename = Str::random(40) . '.' . $extension;
            
            // 确保目录存在
            $directory = 'uploads/images';
            if (!Storage::disk('public')->exists($directory)) {
                Storage::disk('public')->makeDirectory($directory);
            }
            
            // 存储到 public/storage/uploads/images 目录
            $path = $file->storeAs($directory, $filename, 'public');
            
            if (!$path) {
                return response()->json([
                    'code' => 500,
                    'message' => '文件保存失败',
                ], 500);
            }
            
            // 返回可访问的URL - 使用完整的URL而不是相对路径
            // 这样前端就不需要拼接 baseURL，避免前端开发服务器和后端服务器不一致的问题
            $relativeUrl = Storage::url($path);
            $fullUrl = url($relativeUrl);

            return response()->json([
                'code' => 200,
                'message' => '上传成功',
                'data' => [
                    'url' => $fullUrl,
                    'path' => $path,
                    'filename' => $filename,
                ],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'code' => 422,
                'message' => '验证失败：' . implode(', ', $e->errors()['image'] ?? []),
            ], 422);
        } catch (\Exception $e) {
            \Log::error('图片上传失败', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'code' => 500,
                'message' => '上传失败：' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 删除图片
     */
    public function deleteImage(Request $request): JsonResponse
    {
        $request->validate([
            'path' => 'required|string',
        ]);

        try {
            // 从URL中提取路径
            $path = $request->input('path');
            
            // 如果传入的是完整URL，提取路径部分
            if (strpos($path, '/storage/') !== false) {
                $path = str_replace('/storage/', '', $path);
            }

            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
                
                return response()->json([
                    'code' => 200,
                    'message' => '删除成功',
                ]);
            }

            return response()->json([
                'code' => 404,
                'message' => '文件不存在',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => '删除失败：' . $e->getMessage(),
            ], 500);
        }
    }
}
