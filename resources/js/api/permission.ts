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

export const permissionApi = {
  /**
   * 获取权限列表
   */
  getList: (): Promise<ApiResponse<{ permissions: Permission[]; grouped_permissions: Record<string, Permission[]> }>> => {
    return adminApiClient.get('/admin/v1/permissions');
  },
};


