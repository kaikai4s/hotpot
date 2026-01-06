/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

import adminApiClient from '../admin-client';

export interface User {
  id: number;
  openid?: string;
  unionid?: string;
  nickname: string;
  avatar_url?: string | null;
  equipped_title?: string | null;
  phone?: string | null;
  gender?: number | null;
  is_active?: boolean;
  remark?: string | null;
  created_at: string;
  updated_at: string;
  member_points?: {
    level: string;
    level_info?: {
      code: string;
      name: string;
      icon?: string | null;
      color?: string | null;
    } | null;
  } | null;
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

export interface UserDetail extends User {
  inviter?: {
    id: number;
    nickname: string;
    avatar_url?: string | null;
    member_points?: {
      level: string;
      level_info?: {
        code: string;
        name: string;
        icon?: string | null;
        color?: string | null;
      } | null;
    } | null;
  } | null;
  invitees?: Array<{
    id: number;
    nickname: string;
    avatar_url?: string | null;
    created_at: string;
    member_points?: {
      level: string;
      level_info?: {
        code: string;
        name: string;
        icon?: string | null;
        color?: string | null;
      } | null;
    } | null;
  }>;
  all_orders?: any[];
  all_achievements?: any[];
  statistics?: {
    total_points: number;
    available_points: number;
    level: string;
    orders_count: number;
    reviews_count: number;
    coupons_count: number;
    total_spent: number;
  };
  level_info?: {
    code: string;
    name: string;
    icon?: string | null;
    color?: string | null;
  } | null;
}

export interface UserStatistics {
  total_users: number;
  today_users: number;
  this_month_users: number;
  users_with_phone: number;
  users_with_orders: number;
  users_with_points: number;
}

export interface UserListParams {
  search?: string;
  user_id?: number;
  nickname?: string;
  created_from?: string;
  created_to?: string;
  gender?: number | '';
  has_phone?: '0' | '1' | '';
  min_points?: number;
  max_points?: number;
  min_orders?: number;
  max_orders?: number;
  sort_by?: string;
  sort_order?: 'asc' | 'desc';
  page?: number;
  per_page?: number;
}

export interface UpdateUserParams {
  nickname?: string;
  phone?: string;
  gender?: number;
  remark?: string;
  is_active?: boolean;
}

export const userApi = {
  /**
   * 获取用户列表
   */
  getList: (params?: UserListParams) => {
    return adminApiClient.get<{
      code: number;
      message: string;
      data: {
        users: User[];
        pagination: {
          current_page: number;
          total_pages: number;
          total: number;
          per_page: number;
        };
      };
    }>('/admin/v1/users', { params });
  },

  /**
   * 获取用户详情
   */
  getDetail: (userId: number) => {
    return adminApiClient.get<{
      code: number;
      message: string;
      data: {
        user: UserDetail;
      };
    }>(`/admin/v1/users/${userId}`);
  },

  /**
   * 更新用户信息
   */
  update: (userId: number, data: UpdateUserParams) => {
    return adminApiClient.put<{
      code: number;
      message: string;
      data: {
        user: User;
      };
    }>(`/admin/v1/users/${userId}`, data);
  },

  /**
   * 删除用户
   */
  delete: (userId: number) => {
    return adminApiClient.delete<{
      code: number;
      message: string;
    }>(`/admin/v1/users/${userId}`);
  },

  /**
   * 批量删除用户
   */
  batchDelete: (userIds: number[]) => {
    return adminApiClient.post<{
      code: number;
      message: string;
    }>('/admin/v1/users/batch-destroy', { user_ids: userIds });
  },

  /**
   * 获取用户统计信息
   */
  getStatistics: () => {
    return adminApiClient.get<{
      code: number;
      message: string;
      data: {
        statistics: UserStatistics;
      };
    }>('/admin/v1/users/statistics');
  },
};

