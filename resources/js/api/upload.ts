/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

export interface UploadImageResponse {
  url: string;
  path: string;
  filename: string;
}

/**
 * 上传图片
 */
export async function uploadImage(file: File): Promise<UploadImageResponse> {
  const formData = new FormData();
  formData.append('image', file);
  
  // 使用原生fetch API，避免axios处理FormData的问题
  const token = localStorage.getItem('token');
  const response = await fetch('/api/v1/upload/image', {
    method: 'POST',
    headers: {
      Authorization: token ? `Bearer ${token}` : '',
      // 不要手动设置 Content-Type，让浏览器自动设置（包含 boundary）
    },
    body: formData,
  });
  
  if (!response.ok) {
    const errorData = await response.json();
    throw new Error(errorData.message || '上传失败');
  }
  
  const result = await response.json();
  if (result.code !== 200) {
    throw new Error(result.message || '上传失败');
  }
  
  return result.data;
}

