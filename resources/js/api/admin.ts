/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

import adminApiClient from './admin-client';
import type { ApiResponse, Pagination } from '../types';

export interface AdminInfo {
  id: number;
  username: string;
  name: string;
  email: string | null;
  role: 'super_admin' | 'admin' | 'operator';
  roles?: Array<{
    id: number;
    name: string;
    display_name: string;
  }>;
  permissions?: Array<{
    id: number;
    name: string;
    display_name: string;
    group: string;
  }>;
  is_active: boolean;
  last_login_at: string | null;
}

export interface AdminListResponse {
  admins: AdminInfo[];
  pagination: Pagination;
}

export interface AdminCreateRequest {
  username: string;
  name: string;
  email?: string | null;
  password: string;
  role: 'super_admin' | 'admin' | 'operator';
  role_ids?: number[];
  is_active?: boolean;
}

export interface AdminUpdateRequest {
  username?: string;
  name?: string;
  email?: string | null;
  password?: string;
  role?: 'super_admin' | 'admin' | 'operator';
  role_ids?: number[];
  is_active?: boolean;
}

export const adminApi = {
  /**
   * 获取管理员列表
   */
  getList: (params?: {
    page?: number;
    page_size?: number;
    search?: string;
    role?: string;
  }): Promise<ApiResponse<AdminListResponse>> => {
    return adminApiClient.get('/admin/v1/admins', { params });
  },

  /**
   * 获取管理员详情
   */
  getDetail: (id: number): Promise<ApiResponse<{ admin: AdminInfo }>> => {
    return adminApiClient.get(`/admin/v1/admins/${id}`);
  },

  /**
   * 创建管理员
   */
  create: (data: AdminCreateRequest): Promise<ApiResponse<{ admin: AdminInfo }>> => {
    return adminApiClient.post('/admin/v1/admins', data);
  },

  /**
   * 更新管理员信息
   */
  update: (id: number, data: AdminUpdateRequest): Promise<ApiResponse<{ admin: AdminInfo }>> => {
    return adminApiClient.put(`/admin/v1/admins/${id}`, data);
  },

  /**
   * 删除管理员
   */
  delete: (id: number): Promise<ApiResponse<void>> => {
    return adminApiClient.delete(`/admin/v1/admins/${id}`);
  },
};

// 保留原有的认证相关API
export interface AdminLoginRequest {
  username: string;
  password: string;
}

export interface AdminLoginResponse {
  token: string;
  admin: AdminInfo;
}

export const adminAuthApi = {
  /**
   * 管理员登录
   */
  login: (data: AdminLoginRequest): Promise<ApiResponse<AdminLoginResponse>> => {
    return adminApiClient.post('/admin/v1/auth/login', data);
  },

  /**
   * 获取当前管理员信息
   */
  me: (): Promise<ApiResponse<{ admin: AdminInfo }>> => {
    return adminApiClient.get('/admin/v1/auth/me');
  },

  /**
   * 退出登录
   */
  logout: (): Promise<ApiResponse<void>> => {
    return adminApiClient.post('/admin/v1/auth/logout');
  },
};
