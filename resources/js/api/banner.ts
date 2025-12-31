/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

import apiClient from './client';
import adminApiClient from './admin-client';

export interface Banner {
  id: number;
  title: string;
  description?: string;
  image_url: string;
  link_url?: string;
  link_type: 'none' | 'internal' | 'external';
  sort_order: number;
  is_active: boolean;
  start_at?: string;
  end_at?: string;
  created_at: string;
  updated_at: string;
}

export interface ApiResponse<T> {
  code: number;
  message: string;
  data: T;
}

// 前台API
export const bannerApi = {
  /**
   * 获取轮播图列表
   */
  getList: (): Promise<ApiResponse<{ banners: Banner[] }>> => {
    return apiClient.get('/v1/banners');
  },
};

// 后台API
export const adminBannerApi = {
  /**
   * 获取轮播图列表
   */
  getList: (params?: { is_active?: boolean; page_size?: number }): Promise<ApiResponse<{ banners: Banner[]; pagination: any }>> => {
    return adminApiClient.get('/admin/v1/banners', { params });
  },

  /**
   * 获取轮播图详情
   */
  getDetail: (id: number): Promise<ApiResponse<{ banner: Banner }>> => {
    return adminApiClient.get(`/admin/v1/banners/${id}`);
  },

  /**
   * 创建轮播图
   */
  create: (data: Partial<Banner>): Promise<ApiResponse<{ banner: Banner }>> => {
    return adminApiClient.post('/admin/v1/banners', data);
  },

  /**
   * 更新轮播图
   */
  update: (id: number, data: Partial<Banner>): Promise<ApiResponse<{ banner: Banner }>> => {
    return adminApiClient.put(`/admin/v1/banners/${id}`, data);
  },

  /**
   * 删除轮播图
   */
  delete: (id: number): Promise<ApiResponse<void>> => {
    return adminApiClient.delete(`/admin/v1/banners/${id}`);
  },

  /**
   * 批量更新排序
   */
  updateOrder: (banners: Array<{ id: number; sort_order: number }>): Promise<ApiResponse<void>> => {
    return adminApiClient.post('/admin/v1/banners/update-order', { banners });
  },
};

