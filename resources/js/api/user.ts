/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

import adminApiClient from './admin-client';
import type { ApiResponse } from '../types';

export interface User {
  id: number;
  openid: string;
  unionid?: string | null;
  nickname?: string | null;
  avatar_url?: string | null;
  phone?: string | null;
  gender?: number | null;
  is_active?: boolean;
  remark?: string | null;
  created_at: string;
  updated_at: string;
  statistics?: {
    total_points: number;
    available_points: number;
    level: string;
    orders_count: number;
    reviews_count: number;
    coupons_count: number;
    total_spent: number;
  };
}

export interface UserListParams {
  search?: string;
  sort_by?: string;
  sort_order?: 'asc' | 'desc';
  per_page?: number;
  page?: number;
  created_from?: string;
  created_to?: string;
  gender?: number | '';
  has_phone?: '0' | '1' | '';
  min_points?: number;
  max_points?: number;
  min_orders?: number;
  max_orders?: number;
}

export interface UserStatistics {
  total_users: number;
  today_users: number;
  this_month_users: number;
  users_with_phone: number;
  users_with_orders: number;
  users_with_points: number;
}

export interface UserDetail extends User {
  statistics: {
    total_points: number;
    available_points: number;
    frozen_points: number;
    level: string;
    orders_count: number;
    orders_total_amount: number;
    reviews_count: number;
    approved_reviews_count: number;
    coupons_count: number;
    unused_coupons_count: number;
    reservations_count: number;
    point_earned_total: number;
    point_redeemed_total: number;
  };
  level_info?: {
    name: string;
    code: string;
    icon?: string;
    color?: string;
  };
  orders?: any[];
  reviews?: any[];
  userCoupons?: any[];
  pointTransactions?: any[];
  reservations?: any[];
}

export const userApi = {
  /**
   * 获取用户列表
   */
  getList: (params?: UserListParams): Promise<ApiResponse<{ users: User[]; pagination: any }>> => {
    return adminApiClient.get('/admin/v1/users', { params });
  },

  /**
   * 获取用户统计
   */
  getStatistics: (): Promise<ApiResponse<{ statistics: UserStatistics }>> => {
    return adminApiClient.get('/admin/v1/users/statistics');
  },

  /**
   * 获取用户详情
   */
  getDetail: (id: number): Promise<ApiResponse<{ user: UserDetail; statistics: any; level_info: any }>> => {
    return adminApiClient.get(`/admin/v1/users/${id}`);
  },

  /**
   * 更新用户信息
   */
  update: (id: number, data: Partial<User>): Promise<ApiResponse<{ user: User }>> => {
    return adminApiClient.put(`/admin/v1/users/${id}`, data);
  },

  /**
   * 删除用户
   */
  delete: (id: number): Promise<ApiResponse<void>> => {
    return adminApiClient.delete(`/admin/v1/users/${id}`);
  },

  /**
   * 批量删除用户
   */
  batchDelete: (ids: number[]): Promise<ApiResponse<{ deleted: number; failed: number[] }>> => {
    return adminApiClient.post('/admin/v1/users/batch-destroy', { ids });
  },
};


