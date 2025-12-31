/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

import adminApiClient from './admin-client';
import type { ApiResponse } from '../types';

export interface Permission {
  id: number;
  name: string;
  display_name: string;
  description?: string;
  group: string;
  sort_order: number;
}

export interface Role {
  id: number;
  name: string;
  display_name: string;
  description?: string;
  is_system: boolean;
  sort_order: number;
  permissions?: Permission[];
}

export interface CreateRoleData {
  name: string;
  display_name: string;
  description?: string;
  permission_ids?: number[];
}

export const roleApi = {
  /**
   * 获取角色列表
   */
  getList: (): Promise<ApiResponse<{ roles: Role[] }>> => {
    return adminApiClient.get('/admin/v1/roles');
  },

  /**
   * 创建角色
   */
  create: (data: CreateRoleData): Promise<ApiResponse<{ role: Role }>> => {
    return adminApiClient.post('/admin/v1/roles', data);
  },

  /**
   * 更新角色
   */
  update: (id: number, data: Partial<CreateRoleData>): Promise<ApiResponse<{ role: Role }>> => {
    return adminApiClient.put(`/admin/v1/roles/${id}`, data);
  },

  /**
   * 删除角色
   */
  delete: (id: number): Promise<ApiResponse<void>> => {
    return adminApiClient.delete(`/admin/v1/roles/${id}`);
  },
};


