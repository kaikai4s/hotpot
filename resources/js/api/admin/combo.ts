/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

import apiClient from '../client';

export interface ComboDish {
  id?: number;
  combo_id?: number;
  dish_id: number;
  quantity: number;
  sort_order?: number;
  dish?: {
    id: number;
    name: string;
    price: string;
    image_url: string | null;
    status: string;
  };
}

export interface Combo {
  id?: number;
  name: string;
  description?: string | null;
  image_url?: string | null;
  price: number | string;
  stock?: number;
  sold_count?: number;
  is_active?: boolean;
  sort_order?: number;
  created_at?: string;
  updated_at?: string;
  dishes?: ComboDish[];
  original_total?: number;
  savings?: number;
  discount_percent?: number;
}

export interface ApiResponse<T> {
  code: number;
  message: string;
  data?: T;
}

export const adminComboApi = {
  /**
   * 获取套餐列表（管理端）
   */
  getList: (params?: {
    is_active?: boolean;
    search?: string;
    per_page?: number;
    page?: number;
  }): Promise<ApiResponse<{ combos: Combo[]; pagination: any }>> => {
    return apiClient.get('/admin/v1/combos', { params });
  },

  /**
   * 获取套餐详情（管理端）
   */
  getDetail: (id: number): Promise<ApiResponse<{ combo: Combo }>> => {
    return apiClient.get(`/admin/v1/combos/${id}`);
  },

  /**
   * 创建套餐（管理端）
   */
  create: (data: {
    name: string;
    description?: string;
    price: number;
    image_url?: string;
    stock?: number;
    is_active?: boolean;
    sort_order?: number;
    dishes: Array<{ dish_id: number; quantity: number }>;
  }): Promise<ApiResponse<{ combo: Combo }>> => {
    return apiClient.post('/admin/v1/combos', data);
  },

  /**
   * 更新套餐（管理端）
   */
  update: (id: number, data: {
    name?: string;
    description?: string;
    price?: number;
    image_url?: string;
    stock?: number;
    is_active?: boolean;
    sort_order?: number;
    dishes?: Array<{ dish_id: number; quantity: number }>;
  }): Promise<ApiResponse<{ combo: Combo }>> => {
    return apiClient.put(`/admin/v1/combos/${id}`, data);
  },

  /**
   * 删除套餐（管理端）
   */
  delete: (id: number): Promise<ApiResponse<void>> => {
    return apiClient.delete(`/admin/v1/combos/${id}`);
  },
};

