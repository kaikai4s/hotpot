/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

import adminApiClient from '../admin-client';

export interface PointLevel {
  id: number;
  name: string;
  code: string;
  min_points: number;
  discount_type: 'none' | 'percentage' | 'fixed';
  discount_value: number;
  max_discount_amount: number | null;
  min_order_amount: number;
  is_active: boolean;
  sort_order: number;
  description: string | null;
  icon: string | null;
  color: string | null;
  created_at: string;
  updated_at: string;
}

export interface PointLevelListResponse {
  code: number;
  message: string;
  data: {
    levels: PointLevel[];
    pagination: {
      total: number;
      per_page: number;
      current_page: number;
      last_page: number;
    };
  };
}

export interface PointLevelDetailResponse {
  code: number;
  message: string;
  data: {
    level: PointLevel;
  };
}

export interface CreatePointLevelRequest {
  name: string;
  code: string;
  min_points: number;
  discount_type: 'none' | 'percentage' | 'fixed';
  discount_value: number;
  max_discount_amount?: number | null;
  min_order_amount?: number;
  is_active?: boolean;
  sort_order?: number;
  description?: string | null;
  icon?: string | null;
  color?: string | null;
}

export const adminPointLevelApi = {
  /**
   * 获取所有启用的段位（用于下拉选择）
   */
  getActiveLevels(): Promise<{ code: number; message: string; data: { levels: PointLevel[] } }> {
    return adminApiClient.get('/admin/v1/point-levels/active');
  },

  /**
   * 获取段位列表
   */
  getList(params?: {
    search?: string;
    is_active?: boolean;
    sort_by?: string;
    sort_order?: 'asc' | 'desc';
    per_page?: number;
    page?: number;
  }): Promise<PointLevelListResponse> {
    return adminApiClient.get('/admin/v1/point-levels', { params });
  },

  /**
   * 获取段位详情
   */
  getDetail(id: number): Promise<PointLevelDetailResponse> {
    return adminApiClient.get(`/admin/v1/point-levels/${id}`);
  },

  /**
   * 创建段位
   */
  create(data: CreatePointLevelRequest): Promise<PointLevelDetailResponse> {
    return adminApiClient.post('/admin/v1/point-levels', data);
  },

  /**
   * 更新段位
   */
  update(id: number, data: Partial<CreatePointLevelRequest>): Promise<PointLevelDetailResponse> {
    return adminApiClient.put(`/admin/v1/point-levels/${id}`, data);
  },

  /**
   * 删除段位
   */
  delete(id: number): Promise<{ code: number; message: string }> {
    return adminApiClient.delete(`/admin/v1/point-levels/${id}`);
  },

  /**
   * 切换启用状态
   */
  toggleActive(id: number): Promise<PointLevelDetailResponse> {
    return adminApiClient.post(`/admin/v1/point-levels/${id}/toggle-active`);
  },

  /**
   * 批量更新所有用户的段位
   */
  updateAllUserLevels(): Promise<{ code: number; message: string; data: { total: number; updated: number } }> {
    return adminApiClient.post('/admin/v1/point-levels/update-all-levels');
  },

  /**
   * 获取所有已使用的图标列表
   */
  getUsedIcons(): Promise<{ code: number; message: string; data: { icons: string[] } }> {
    return adminApiClient.get('/admin/v1/point-levels/used-icons');
  },
};

