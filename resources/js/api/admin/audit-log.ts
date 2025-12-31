/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

import adminApiClient from '../admin-client';
import type { ApiResponse, Pagination } from '../../types';

export interface AuditLog {
  id: number;
  user_type: string;
  user_id: number;
  action: string;
  model_type: string | null;
  model_id: number | null;
  old_values: Record<string, any> | null;
  new_values: Record<string, any> | null;
  ip_address: string | null;
  user_agent: string | null;
  description: string | null;
  created_at: string;
  updated_at: string;
  admin?: {
    id: number;
    name: string;
    username: string;
  };
}

export interface AuditLogFilters {
  user_id?: number;
  action?: string;
  model_type?: string;
  model_id?: number;
  date_from?: string;
  date_to?: string;
  page?: number;
  page_size?: number;
}

export interface AuditLogListResponse {
  logs: AuditLog[];
  pagination: Pagination;
}

export interface ActionStatistics {
  action: string;
  count: number;
}

export interface AdminStatistics {
  user_id: number;
  count: number;
  admin?: {
    id: number;
    name: string;
    username: string;
  };
}

export interface AuditLogStatisticsResponse {
  action_statistics: ActionStatistics[];
  admin_statistics: AdminStatistics[];
}

export const adminAuditLogApi = {
  /**
   * 获取操作日志列表
   */
  getLogs: (filters?: AuditLogFilters): Promise<ApiResponse<AuditLogListResponse>> => {
    return adminApiClient.get('/admin/v1/audit-logs', { params: filters });
  },

  /**
   * 获取操作日志详情
   */
  getLog: (id: number): Promise<ApiResponse<AuditLog>> => {
    return adminApiClient.get(`/admin/v1/audit-logs/${id}`);
  },

  /**
   * 获取操作日志统计
   */
  getStatistics: (filters?: { date_from?: string; date_to?: string }): Promise<ApiResponse<AuditLogStatisticsResponse>> => {
    return adminApiClient.get('/admin/v1/audit-logs/statistics', { params: filters });
  },
};

