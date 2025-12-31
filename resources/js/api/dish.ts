/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

import apiClient from './client';
import type { ApiResponse, Pagination } from '../types';

export interface Dish {
  id: number;
  name: string;
  description: string | null;
  price: number;
  image_url: string | null;
  category_id: number;
  status: 'available' | 'sold_out' | 'unavailable';
  average_rating: number;
  review_count: number;
  sales_count: number;
  sort_order: number;
  created_at: string;
  updated_at: string;
  category?: {
    id: number;
    name: string;
  };
}

export interface DishCategory {
  id: number;
  name: string;
  description: string | null;
  is_active: boolean;
  sort_order: number;
}

interface DishesListResponse {
  dishes: Dish[];
  pagination: Pagination;
}

interface CategoriesResponse {
  categories: DishCategory[];
}

export const dishApi = {
  /**
   * 获取菜品列表
   */
  getList: (params?: {
    category_id?: number;
    search?: string;
    sort?: 'default' | 'price_asc' | 'price_desc' | 'rating_desc' | 'sales_desc';
    per_page?: number;
    page?: number;
  }): Promise<ApiResponse<DishesListResponse>> => {
    return apiClient.get('/v1/dishes', { params });
  },

  /**
   * 获取菜品分类列表
   */
  getCategories: (): Promise<ApiResponse<CategoriesResponse>> => {
    return apiClient.get('/v1/dishes/categories');
  },

  /**
   * 获取菜品详情
   */
  getDetail: (id: number): Promise<ApiResponse<{ dish: Dish }>> => {
    return apiClient.get(`/v1/dishes/${id}`);
  },
};

