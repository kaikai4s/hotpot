/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

import adminApiClient from './admin-client';
import type { ApiResponse } from '../types';

export interface Configuration {
  id: number;
  key: string;
  value: any;
  type: 'string' | 'integer' | 'boolean' | 'json';
  group: string;
  label: string;
  description?: string;
  sort_order: number;
  is_public: boolean;
}

export interface BatchUpdateConfig {
  key: string;
  value: any;
}

export const configApi = {
  /**
   * 获取配置列表
   */
  getList: (group?: string): Promise<ApiResponse<{ configs: Configuration[]; grouped: Record<string, Configuration[]> }>> => {
    return adminApiClient.get('/admin/v1/configs', { params: group ? { group } : {} });
  },

  /**
   * 获取单个配置
   */
  getDetail: (key: string): Promise<ApiResponse<{ config: Configuration }>> => {
    return adminApiClient.get(`/admin/v1/configs/${key}`);
  },

  /**
   * 批量更新配置
   */
  batchUpdate: (configs: BatchUpdateConfig[]): Promise<ApiResponse<void>> => {
    return adminApiClient.post('/admin/v1/configs/batch-update', { configs });
  },

  /**
   * 更新单个配置
   */
  update: (id: number, data: Partial<Configuration>): Promise<ApiResponse<{ config: Configuration }>> => {
    return adminApiClient.put(`/admin/v1/configs/${id}`, data);
  },
};


