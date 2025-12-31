/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

import apiClient from './client';

export interface ComboDish {
  id: number;
  combo_id: number;
  dish_id: number;
  quantity: number;
  sort_order: number;
  dish?: {
    id: number;
    name: string;
    price: string;
    image_url: string | null;
    status: string;
  };
}

export interface Combo {
  id: number;
  name: string;
  description: string | null;
  image_url: string | null;
  price: string;
  stock: number;
  sold_count: number;
  is_active: boolean;
  sort_order: number;
  created_at: string;
  updated_at: string;
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

export const comboApi = {
  /**
   * 获取套餐列表（前端用户）
   */
  getList: (params?: {
    search?: string;
    sort?: 'default' | 'price_asc' | 'price_desc' | 'savings_desc';
    per_page?: number;
    page?: number;
  }): Promise<ApiResponse<{ combos: Combo[]; pagination: any }>> => {
    return apiClient.get('/v1/combos', { params });
  },

  /**
   * 获取套餐详情（前端用户）
   */
  getDetail: (id: number): Promise<ApiResponse<{ combo: Combo }>> => {
    return apiClient.get(`/v1/combos/${id}`);
  },
};

